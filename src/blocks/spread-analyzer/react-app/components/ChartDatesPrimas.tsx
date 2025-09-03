import React from 'react'
import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { ChartDataType } from '../../../../types/types';

function ChartDatesPrimas({ chartData }: { chartData: Array<ChartDataType> }) {
	return (
		<div>
					<ResponsiveContainer width="100%" height={400}>
						<LineChart data={chartData}>
							<CartesianGrid strokeDasharray="3 3" />
							<XAxis dataKey="date" tick={{ fontSize: 12 }} angle={-45} textAnchor="end" height={80} />
							<YAxis domain={[0, 500]} tick={{ fontSize: 12 }} />
							<Tooltip formatter={(value, name) => [value, name]} labelFormatter={(label) => `Date: ${label}`} />
							<Legend />
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
						</LineChart>
					</ResponsiveContainer>
		</div>
	)
}

export default ChartDatesPrimas