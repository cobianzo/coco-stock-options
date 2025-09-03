import { extractFormalStrikePrice } from './sanitazors';

import { WPStockOptionInfo, WPAllOptionsData } from 'src/types/types';


/**
 * Retrieves option information for a specific date and strike price from the options data
 *
 * @param {WPAllOptionsData} optionsData - Object containing all options data organized by date and strike price
 * @param {string} date - The date to look up options for
 * @param {number} strikePrice - The strike price to search for
 *
 * @returns {WPStockOptionInfo | null} The option information if found, null otherwise { "BXMT250815P": {} , ... }
 */
export function getOptionInfoByDateAndStrike(optionsData: WPAllOptionsData, date: string, strikePrice: number): WPStockOptionInfo | null {
	if (!optionsData || Object.keys(optionsData).length === 0) return null;

	const strikesForDate = optionsData[date]; // [ 00115000: { ... }, 00135000: { ... }, ...] the keys are formal stike prices

	const formalPrice: string = extractFormalStrikePrice(strikePrice as number);
	const infoForOption: WPStockOptionInfo = strikesForDate?.[formalPrice];

	return infoForOption || null;
}

/**
 * Assuming that the update date of the first element is the updated date of all the set of options.
 * @param optionsData
 * @returns string
 */
export function getLatestUpdateFromFirstElement(optionsData: WPAllOptionsData): string {
	if ( ! optionsData || Object.keys(optionsData).length == 0 ) return '';
	const firstDate = Object.keys(optionsData)[0];
	if ( ! firstDate ) return '';
	const firstStrike = Object.keys(optionsData[firstDate])[0];
	if ( ! firstStrike ) return '';
  const firstOption = optionsData[firstDate][firstStrike];
  return firstOption?.cboe_timestamp ?? '';
}