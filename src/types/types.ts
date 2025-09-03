// define the type for a response for a date option info:
export type WPStockOptionInfo = {
	cboe_timestamp: string;
	last_update: string;
	bid: number;
	ask: number;
	last: number;
	volume: number;
	open_interest: number;
	implied_volatility: number;
	delta: number;
	gamma: number;
	theta: number;
	vega: number;
	rho: number;
};

export type WPAllOptionsData = {
    [expirationDate: string]: {
        [strike: string]: WPStockOptionInfo;
    };
} | Record<string, never>;
/* This is how it looks
{
  "BXMT250815P": {
    "00011000": {
      "cboe_timestamp": "2025-08-02 23:07:36",
			"bid": 0.1,
			"ask": 0.75,
			...
    },
		"00012000": { ...}
	},
	"BXMT250816P": {
	...
	},
	...
}
*/
export interface ChartDataType {
    date: string;
    primaSell: number;
    primaBuy: number;
}