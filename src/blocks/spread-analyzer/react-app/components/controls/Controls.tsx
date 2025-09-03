import React, { useState } from 'react';

import ValidNumberInput from './ValidNumberInput';

function Controls({
	validStrikes,
	strikeSell,
	setStrikeSell,
	strikeBuy,
	setStrikeBuy,
}: {
	validStrikes: Array<number>;
	strikeSell: number;
	setStrikeSell: (newVal: number) => void;
	strikeBuy: number;
	setStrikeBuy: (newVal: number) => void;
}) {
	const [isTicksGapLocked, setIsTicksGapLocked] = useState(false);
	const [ticksGap, setTicksGap] = useState(strikeSell - strikeBuy);

	React.useEffect(() => {
		// se calcula el gap entre los strikes
		setTicksGap(Math.abs(strikeSell - strikeBuy));
	}, [isTicksGapLocked, strikeSell, strikeBuy]);

	// Handles onClick
	// ======================
	const handleStrikeSellBuyChange = (newVal: number, updated: 'sell' | 'buy') => {
		const value = newVal || 0;
		if (updated === 'sell') {
			setStrikeSell(value);
		} else {
			setStrikeBuy(value);
		}

		// update automatically value of the other leg of the spread, based on the ticksGap selected.
		if (isTicksGapLocked) {
			const diff = value + (updated === 'sell' ? -1 : 1) * ticksGap;
			// checl if the new diff value exists in the list of valid values.
			if (validStrikes.includes(diff)) {
				if (updated === 'sell') {
					setStrikeBuy(diff);
				} else {
					setStrikeSell(diff);
				}
			}
		} else {
		}
	};

	/**
	 * ===========
	 * JSX
	 * ===========
	 */
	return (

			<div className="three-column-layout">
				{/* Columna del medio - inputs */}
				<div className="column column-center">
					<div className="input-group">
						<label htmlFor="strikeSell">Strike Sell ({strikeSell}):</label>
						{strikeSell && (
							<ValidNumberInput
								defaultValue={strikeSell}
								setDefaultValue={setStrikeSell}
								validValues={validStrikes}
								onChange={(newVal: number) => handleStrikeSellBuyChange(newVal, 'sell')}
							/>
						)}
					</div>
					<div className="input-group">
						<label htmlFor="strikeBuy">Strike Buy ({strikeBuy}):</label>
						{strikeBuy && (
							<ValidNumberInput
								defaultValue={strikeBuy}
								setDefaultValue={setStrikeBuy}
								validValues={validStrikes}
								onChange={(newVal: number) => handleStrikeSellBuyChange(newVal, 'buy')}
							/>
						)}
					</div>
				</div>

				{/* Right column - Ticks Gap control */}
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
			</div>

	);
}

export default Controls;
