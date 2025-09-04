import React, { useState } from 'react';

import { FiltersType } from 'src/types/types';
import { LocalStorageManager } from '../../helpers/localStorageManager';
import UrlGenerator from '../UrlGenerator';
import ValidNumberInput from './ValidNumberInput';
import { isValidDate } from '../../helpers/helpers';
import Popup from '../popup/Popup'; // Import the Popup component

// Types
interface PropsType {
	ticker: string;
	validStrikes: Array<number>;
	strikeSell: number;
	filters: FiltersType;
	setFilters: (newVal: FiltersType) => void;
	setStrikeSell: (newVal: number) => void;
	strikeBuy: number;
	setStrikeBuy: (newVal: number) => void;
}

function Controls({ ticker, validStrikes, filters, setFilters, strikeSell, setStrikeSell, strikeBuy, setStrikeBuy }: PropsType) {
	// State Vars
	const [isTicksGapLocked, setIsTicksGapLocked] = useState(false);
	const [ticksGap, setTicksGap] = useState(strikeSell - strikeBuy);
	const [isPopupOpen, setIsPopupOpen] = useState(false); // State for the popup

	// Computed - calculate gap depending on sell and buy strikes.
	React.useEffect(() => {
		// se calcula el gap entre los strikes
		setTicksGap(Math.abs(strikeSell - strikeBuy));
	}, [isTicksGapLocked, strikeSell, strikeBuy]);

	// Handles - when the inputs are modified.
	// ======================
	const handleStrikeSellBuyChange = (newVal: number, updated: 'sell' | 'buy') => {
		const value = newVal || 0;
		let sellValueForLocalStorage;
		let buyValueForLocalStorage;

		if (updated === 'sell') {
			setStrikeSell(value);
			sellValueForLocalStorage = value;
		} else {
			setStrikeBuy(value);
			buyValueForLocalStorage = value;
		}

		// update automatically value of the other leg of the spread, based on the ticksGap selected.
		if (isTicksGapLocked) {
			const diff = value + (updated === 'sell' ? -1 : 1) * ticksGap;
			// checl if the new diff value exists in the list of valid values.
			if (validStrikes.includes(diff)) {
				if (updated === 'sell') {
					setStrikeBuy(diff);
					sellValueForLocalStorage = diff;
				} else {
					setStrikeSell(diff);
					buyValueForLocalStorage = diff;
				}
			}
		}

		if (sellValueForLocalStorage) LocalStorageManager.save( `${ticker}_strikesell`, sellValueForLocalStorage);
		if (buyValueForLocalStorage) LocalStorageManager.save( `${ticker}_strikebuy`, buyValueForLocalStorage);
	};

	/**
	 * ===========
	 * JSX
	 * ===========
	 */
	return (
		<>
			<div className="three-column-layout">
				{/* Columna del medio - inputs */}
				<div className="column column-center">
					<div className="input-group">
						<label htmlFor="strikeSell">Strike Sell ({strikeSell}):</label>
						{strikeSell && (
							<ValidNumberInput
								realValue={strikeSell}
								validValues={validStrikes}
								onChange={(newVal: number) => handleStrikeSellBuyChange(newVal, 'sell')}
							/>
						)}
					</div>
					<div className="input-group">
						<label htmlFor="strikeBuy">Strike Buy ({strikeBuy}):</label>
						{strikeBuy && (
							<ValidNumberInput
								realValue={strikeBuy}
								validValues={validStrikes}
								onChange={(newVal: number) => handleStrikeSellBuyChange(newVal, 'buy')}
							/>
						)}
					</div>
				</div>

				{/* 3rd column - Ticks Gap control */}
				<div className="column column-right">
					<label htmlFor="ticks-gap">Prices Gap:</label>
					<div className="input-group input-group-ticks-gap">
						<button className="lock-button" onClick={() => setIsTicksGapLocked(!isTicksGapLocked)}>
							<span className={`dashicons dashicons-${isTicksGapLocked ? 'lock' : 'unlock'}`}></span>
						</button>
						<input
							type="number"
							id="ticks-gap"
							name="ticks-gap"
							value={ticksGap}
							// onChange={(e) => !isTicksGapLocked && setTicksGap(Number(e.target.value))}
							readOnly
							disabled
						/>
					</div>
				</div>
				<div className="column column-4th">
					<UrlGenerator strikeSell={strikeSell} strikeBuy={strikeBuy} />

					<div className="input-group">
							<label htmlFor="maxdate-selector">Filter by limit Date:</label>
							<div className="maxdate-input-container">
								<input
										type="date"
										id="maxdate-selector"
										name="maxdate-selector"
										className="date-input"
										value={filters.maxDate || ''}
										onChange={(e) => setFilters({...filters, maxDate: e.target.value})}
								/>
								{filters.maxDate &&  <button onClick={() => setFilters({...filters, maxDate: ''})}>X</button> }
							</div>
							{ isValidDate(filters.maxDate || '') ? <small>Filter active</small>: <small>Filter not active {filters.maxDate}</small> }
					</div>
					<div className="Button">
						<button onClick={() => setIsPopupOpen(true)}>Test</button>
					</div>
				</div>
			</div>

			<Popup isOpen={isPopupOpen} onClose={() => setIsPopupOpen(false)} title="@cobianzo">
				<p>This is a demo version of the plugin. Enojy it!</p>
			</Popup>
		</>
	);
}

export default Controls;
