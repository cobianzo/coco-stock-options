import React from 'react';
import { Bar, CartesianGrid, ComposedChart, Legend, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { ChartDataType, FiltersType } from '../../../../types/types';
import { isValidDate } from '../helpers/helpers';

interface PropsType { chartData: Array<ChartDataType>, setChartData: (data: Array<ChartDataType>) => void, filters: FiltersType }

function ChartDatesPrimas({ chartData, setChartData, filters }: PropsType) {

	// Handle Max value of the chart
	const [maxValue, setMaxValue] = React.useState<number>(0);
	const [originalChartData, setOriginalChartData] = React.useState<Array<ChartDataType>|null>(null);

	React.useEffect(() => {
		setOriginalChartData(chartData);
	}, []);

	React.useEffect(() => {
		const maxPrimaSell = Math.max(...chartData.map((data) => data.primaSell || 0));
		setMaxValue(maxPrimaSell);

	}, [chartData]);

	// Applying filters
	React.useEffect(() => {
		// filter to stop showing after the maxDate.
		if (filters.maxDate && isValidDate(filters.maxDate)) {
			const filteredChartDate = chartData.filter((data) => (data.formalDate).getTime() <= new Date(filters.maxDate || '').getTime())
			setChartData(filteredChartDate);
		} else if (originalChartData) {
			debugger;
			setChartData(originalChartData);
		}
	}, [filters]);

	return (
		<div>
			<ResponsiveContainer width="100%" height={400}>
				<ComposedChart data={chartData}>
					<CartesianGrid strokeDasharray="3 3" />
					<XAxis dataKey="dateLabel" tick={{ fontSize: 12 }} angle={-45} textAnchor="end" height={80} />
					<YAxis domain={[0, maxValue]} tick={{ fontSize: 12 }} />
					<Tooltip
							formatter={(value, name) => [value, name]}
							labelFormatter={(label) => `Date: ${label}`}
						/>
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
		</div>
	);
}

export default ChartDatesPrimas;
