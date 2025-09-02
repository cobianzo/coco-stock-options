import React from 'react';
import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import ValidNumberInput from './controls/ValidNumberInput';
import { extractDateFromSymbol, extractFormalStrikePrice, sanitizeStrikePrice } from './helpers/sanitazors';
import useGetStockOptions from './hooks/useGetStockOptions';
import useGetStockPost from './hooks/useGetStockPost';

const SHARES_PER_CONTRACT = 100;
const CONTRACTS = 1;

const SpreadAnalyzerApp = ({ side, stockId }: { side: 'PUT' | 'CALL'; stockId: number }) => {
	const { options, loading: optionsLoading, error: optionsError } = useGetStockOptions( stockId, side.toLowerCase() as 'put' | 'call');
	const { post, loading: postLoading, error: postError } = useGetStockPost(stockId);

	const [spreadDates, setSpreadDates] = React.useState<string[]>([]);
	// Estados para los comandos de edición
	const [strikeSell, setStrikeSell] = React.useState<number>(0.0);
	const [strikeBuy, setStrikeBuy] = React.useState<number>(0.0);
	const [validStrikes, setValidStrikes] = React.useState<number[]>([]);

	// Datos para el gráfico
	const [chartData, setChartData] = React.useState<Array<{ date: string; primaSell: number; primaBuy: number }>>([]);

	React.useEffect(() => {
		if (!options) return;

		const dates = Object.keys(options); // .map( date6digits => extractDateFromSymbol(date6digits).toLocaleDateString() );
		setSpreadDates(dates);

		// Obtener strikes válidos
		let strikes: number[] = [];
		Object.keys(options).forEach((date6digits) => {
			const strikesForTheDate = options[date6digits];
			strikes = strikes.concat(Object.keys(strikesForTheDate).map((a) => sanitizeStrikePrice(a)));
			strikes.sort((a, b) => a - b);
			strikes = [...new Set(strikes)]; // Remove duplicates
		});
		setValidStrikes(strikes);

		const defaultSellStrike = strikes[Math.floor(strikes.length / 2) + 1]; // 00011000
		const defaultBuyStrike = strikes[Math.floor(strikes.length / 2) - 1];
		setStrikeSell(defaultSellStrike);
		setStrikeBuy(defaultBuyStrike);
	}, [options]);

	// Esto pinta la grafica
	React.useEffect(() => {
		// Preparar datos para el gráfico
		const data = spreadDates.map((date) => {
			// get all bid values for the date
			const strikesForDate = options[date]; // [ 00115000, 00135000, ...]

			const formalPrice = extractFormalStrikePrice(strikeSell);
			const infoForPutOrCall = strikesForDate[formalPrice];

			return {
				date: extractDateFromSymbol(date).toLocaleDateString(),
				primaSell: (infoForPutOrCall?.bid ?? 0) * SHARES_PER_CONTRACT * CONTRACTS,
				primaBuy: (infoForPutOrCall?.ask ?? 0) * SHARES_PER_CONTRACT * CONTRACTS,
			};
		});
		setChartData(data);
	}, [spreadDates, strikeSell, strikeBuy]);

	return (
		<div>
			<h3>
				{side == 'PUT' ? 'Bear Put' : 'Bull Call'} Spread Analyzer for {post?.title?.rendered || 'Unknown'} {`(${post?.id})` || ''}{' '}
			</h3>
			{postLoading && <p>Loading post data...</p>}
			{postError && <p>Error fetching post: {postError.message}</p>}

			<h4>Valid Strikes:</h4>
			<pre>
				<code>{JSON.stringify(validStrikes, null, 2)}</code>
			</pre>

			{/* Panel de comandos de edición */}
			<div className="editing-commands-panel">
				<h4>Edit Spread</h4>
				<div className="three-column-layout">
					{/* Columna izquierda - vacía */}
					<div className="column column-left"></div>

					{/* Columna del medio - inputs */}
					<div className="column column-center">
						<div className="input-group">
							<label htmlFor="strikeSell">Strike Sell ({strikeSell}):</label>
							{strikeSell && (
								<ValidNumberInput
									defaultValue={strikeSell}
									validValues={validStrikes}
									onChange={(newVal: number) => setStrikeSell(newVal || 0)}
								/>
							)}
						</div>
						<div className="input-group">
							<label htmlFor="strikeBuy">Strike Buy ({strikeBuy}):</label>
							{strikeBuy && (
								<ValidNumberInput
									defaultValue={strikeBuy}
									validValues={validStrikes}
									onChange={(newVal: number) => setStrikeBuy(newVal || 0)}
								/>
							)}
						</div>
					</div>

					{/* Columna derecha - vacía por ahora */}
					<div className="column column-right"></div>
				</div>
			</div>

			{/* Gráfico de prima del spread */}
			{chartData.length > 0 && (
				<div className="chart-container">
					<h4>Prima Sell Over Time</h4>
					<p>Latest update: {   options[Object.keys(options)[0]][Object.keys(options[Object.keys(options)[0]])[0]].cboe_timestamp   }</p>

					{/* TODO: show latest date of update for the options data */}
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
			)}

			<h4>Stock Options Data:</h4>
			{optionsLoading && <p>Loading options data...</p>}
			{optionsError && <p>Error fetching options: {optionsError.message}</p>}
			{options && (
				<pre>
					<code>{Object.keys(options).length} dates</code>
					<br />
					<code>{spreadDates.join(', ')}</code>
				</pre>
			)}
		</div>
	);
};

export default SpreadAnalyzerApp;
