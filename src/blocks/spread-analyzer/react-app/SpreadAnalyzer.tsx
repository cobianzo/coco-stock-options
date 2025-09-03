// External
import React from 'react';

// Internal
import { extractDateFromSymbol } from './helpers/sanitazors';

// Hooks and helpers
import useGetStockOptions from './hooks/useGetStockOptions';
import useGetStockPost from './hooks/useGetStockPost';
import useValidStrikes from './hooks/useValidStrikes';
import { getOptionInfoByDateAndStrike, getLatestUpdateFromFirstElement } from './helpers/helpers';

// Types
import { WPStockOptionInfo, WPAllOptionsData, ChartDataType } from 'src/types/types';
import ChartDatesPrimas from './components/ChartDatesPrimas';
import Controls from './components/controls/Controls';
import ReloadData from './components/controls/ReloadData';

// Consts
const SHARES_PER_CONTRACT = 100;
const CONTRACTS = 1;

const SpreadAnalyzerApp = ({ side, stockId }: { side: 'PUT' | 'CALL'; stockId: number }) => {

	// Custom hooks
	// ==============================
	const { optionsData, loading: optionsLoading, error: optionsError, refetch: refetchOptionsData }: {
			optionsData: WPAllOptionsData, loading: boolean, error: Error | null, refetch: () => void
		} = useGetStockOptions( stockId, side.toLowerCase() as 'put' | 'call' );
	const { validStrikes } = useValidStrikes(optionsData);
	const { post, loading: postLoading, error: postError } = useGetStockPost(stockId);

	// Reactive vars of the component
	// ==============================
	const [spreadDates, setSpreadDates] = React.useState<string[]>([]); // all dates like [ "BXMT250919P", ...]
	const [strikeSell, setStrikeSell] = React.useState<number>(0.0);
	const [strikeBuy, setStrikeBuy] = React.useState<number>(0.0);

	// Model of the chart: X => dates, Y => primas (for selling and for buying), this is set from optionsData
	const [chartData, setChartData] = React.useState<Array<ChartDataType>>([]);


	// Init things when loading
	// ==============================

	// INIT >>> values for initial inputs for strike sell and buy
	React.useEffect(() => {
		// set initial valua around the half of all the list of valid strikes
    const defaultSellStrike = validStrikes[Math.floor(validStrikes.length / 2) + 1];
    const defaultBuyStrike = validStrikes[Math.floor(validStrikes.length / 2) - 1];
    setStrikeSell(defaultSellStrike);
    setStrikeBuy(defaultBuyStrike);
	}, [validStrikes]);


	// INIT >>> All valid dates as [ "BXMT250919P", ...]
	React.useEffect(() => {
		if (!optionsData) return;
		const dates = Object.keys(optionsData);
		setSpreadDates(dates);
	}, [optionsData]);


	// INIT and update chart >>> Paints chart
	React.useEffect(() => {
		if (!optionsData || Object.keys(optionsData).length === 0 || spreadDates.length === 0) return;
		if (!strikeSell || ! strikeBuy) return;
		// Preparar datos para el gráfico
		const chartData = spreadDates.map((date) => {

			const sellInfo: WPStockOptionInfo | null = getOptionInfoByDateAndStrike(optionsData, date, strikeSell as number);
			const buyInfo: WPStockOptionInfo | null = getOptionInfoByDateAndStrike(optionsData, date, strikeBuy as number);

			const MULTIPLIER = SHARES_PER_CONTRACT * CONTRACTS;

			const dateForLabel = extractDateFromSymbol(date);
			const dateLabel = `${dateForLabel.toLocaleDateString()} (${Math.floor((dateForLabel.getTime() - Date.now()) / (1000 * 60 * 60 * 24))} days) `
			const primaSellRaw = sellInfo?.bid ? sellInfo.bid : null;
			const primaSell = primaSellRaw ? Number((primaSellRaw * MULTIPLIER).toFixed(2)) : null;
			const primaBuy = buyInfo?.ask ? Number((buyInfo.ask * MULTIPLIER).toFixed(2)) : null;
			const profit = primaSell !== null && primaBuy !== null ? primaSell - primaBuy : null;
			const maxLoss = strikeSell - strikeBuy - (profit?? 0);

			const breakEven = primaSell !== null && profit !== null && Number((strikeSell - profit/MULTIPLIER).toFixed(3));
			return {
				date: dateLabel,
				primaSell,
				primaBuy,
				breakEven,
				profit,
				maxLoss
			};
		});

		setChartData(chartData);

	}, [spreadDates, strikeSell, strikeBuy, optionsData]);




	/**
	 * ===========
	 * JSX
	 * ===========
	 */
	return (
		<div>
			<h3>
				{side == 'PUT' ? 'Bull Put' : 'Bear Call'} Spread Analyzer for {
					post?.title?.rendered || 'Unknown'
				} {`(${post?.id})` || ''}{' '}
			</h3>
			<p>Analyzing by strike prices</p>

			{postLoading && <p>Loading post data...</p>}
			{postError && <p>Error fetching post: {postError.message}</p>}

			{/* Panel de comandos de edición */}
			<div className="editing-commands-panel">
				<div className="editing-commands-panel-left">
					Contracts: { CONTRACTS } <br/>
					Shares <small>({SHARES_PER_CONTRACT} per contract)</small> { CONTRACTS * SHARES_PER_CONTRACT }
				</div>
				<div>
					<Controls validStrikes={validStrikes || []}
						strikeSell={strikeSell} setStrikeSell={setStrikeSell}
						strikeBuy={strikeBuy} setStrikeBuy={setStrikeBuy}
						/>
				</div>
			</div>

			{/* Gráfico de prima del spread */}
			{chartData.length > 0 && (
				<div className="chart-container">
					<div className="chart-container-title">
						<h4>Prima Sell Over Time</h4>
						<ReloadData
							stockPostTitle={post?.title?.rendered ?? null}
							refetch={refetchOptionsData}
						/>
						<small>
							Latest update: {getLatestUpdateFromFirstElement(optionsData)}
							<br/>
							({Math.floor((Date.now() - new Date(getLatestUpdateFromFirstElement(optionsData)).getTime()) / (1000 * 60 * 60 * 24))}
							&nbsp; days ago)
						</small>
					</div>
					<ChartDatesPrimas chartData={chartData} />
				</div>
			)}

			<h4>Stock Options Data:</h4>
			{optionsLoading && <p>Loading options data...</p>}
			{optionsError && <p>Error fetching options: {optionsError instanceof Error ? optionsError.message : String(optionsError)}</p>}

		</div>
	);
};

export default SpreadAnalyzerApp;
