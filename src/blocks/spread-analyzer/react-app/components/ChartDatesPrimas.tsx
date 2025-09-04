import React from 'react';
import { Bar, CartesianGrid, ComposedChart, Legend, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { ChartDataType, FiltersType } from '../../../../types/types';
import { isValidDate } from '../helpers/helpers';

interface PropsType {
	chartData: Array<ChartDataType>;
	setChartData: (data: Array<ChartDataType>) => void;
	filters: FiltersType;
}

function ChartDatesPrimas({ chartData, setChartData, filters }: PropsType) {
	// Handle Max value of the chart
	const [maxValue, setMaxValue] = React.useState<number>(0);
	const [filteredChartData, setFilteredChartData] = React.useState<Array<ChartDataType> | null>(null);

	// init on load first time
	React.useEffect(() => {
		const maxPrimaSell = Math.max(...chartData.map((data) => data.primaSell || 0));
		setMaxValue(maxPrimaSell);

		const filteredCD = applyFiltersToChartData(chartData, filters);
		setFilteredChartData(filteredCD);
	}, [chartData]);

	// Applying filters
	React.useEffect(() => {
		if (!chartData) return;
		setFilteredChartData( applyFiltersToChartData(chartData, filters) );
	}, [filters]);

	return (
		<div>
			{filteredChartData && (
				<ResponsiveContainer width="100%" height={400}>
					<ComposedChart data={filteredChartData}>
						<CartesianGrid strokeDasharray="3 3" />
						<XAxis dataKey="dateLabel" tick={{ fontSize: 12 }} angle={-45} textAnchor="end" height={80} />
						<YAxis domain={[0, maxValue]} tick={{ fontSize: 12 }} />
						<Tooltip formatter={(value, name) => [value, name]} labelFormatter={(label) => `Date: ${label}`} />
						<Legend />
						<Bar dataKey="profit" fill="#8884d8" barSize={20} />
						<Bar dataKey="maxLoss" fill="#ef4444" barSize={20} />
						<Line
							type="monotone"
							dataKey="primaSell"
							stroke="#3b82f6"
							strokeWidth={2}
							dot={{ fill: '#3b82f6', strokeWidth: 2, r: 4 }}
							activeDot={{ r: 6 }}
						/>
						<Line
							type="monotone"
							dataKey="primaBuy"
							stroke="#2f6008"
							strokeWidth={2}
							dot={{ fill: '#2f6008', strokeWidth: 2, r: 4 }}
							activeDot={{ r: 6 }}
						/>
						<Line
							type="monotone"
							dataKey="breakEven"
							stroke="#9ca3af"
							strokeWidth={1}
							strokeDasharray="3 3"
							dot={{ fill: '#9ca3af', strokeWidth: 3, r: 3 }}
							activeDot={{ r: 4 }}
						/>
					</ComposedChart>
				</ResponsiveContainer>
			)}
		</div>
	);
}

export default ChartDatesPrimas;

/**
 *
 * @param chartDataParam
 * @returns
 */
function applyFiltersToChartData(chartDataParam: ChartDataType[], filtersParam: FiltersType): ChartDataType[] {
		if (!chartDataParam) return chartDataParam;
		let filtededChartData = chartDataParam;
		// filter to stop showing after the maxDate.
		if (filtersParam.maxDate && isValidDate(filtersParam.maxDate)) {
			filtededChartData = chartDataParam.filter((data) => data.formalDate.getTime() <= new Date(filtersParam.maxDate || '').getTime());
		}
		// the data can be filtered, or we can have removed the filter
		return filtededChartData;
	}