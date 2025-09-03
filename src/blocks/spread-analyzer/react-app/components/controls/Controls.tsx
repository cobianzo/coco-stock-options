import React, { useState } from 'react';

import ValidNumberInput from './ValidNumberInput';

function Controls({ validStrikes, strikeSell, setStrikeSell, strikeBuy, setStrikeBuy }: {
	validStrikes: Array<number>;
	strikeSell: number;
	setStrikeSell: (newVal: number) => void;
	strikeBuy: number;
	setStrikeBuy: (newVal: number) => void;
}) {
	const [isTicksGapLocked, setIsTicksGapLocked] = useState(false);
	const [ticksGap, setTicksGap] = useState(0);

	React.useEffect(() => {
		// si se activa el lock, se calcula el gap entre los strikes
		if (isTicksGapLocked) {
			setTicksGap(Math.abs(strikeSell - strikeBuy));
		} else {

		}
	}, [isTicksGapLocked, strikeSell, strikeBuy]);

	return (
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

				{/* Right column - Ticks Gap control */}
				<div className="column column-right">
					<button
						className="lock-button"
						onClick={() => setIsTicksGapLocked(!isTicksGapLocked)}
					>
						<span className={`dashicons dashicons-${isTicksGapLocked ? 'lock' : 'unlock'}`}></span>
					</button>
					<div className="input-group">
						<label htmlFor="ticksGap">Ticks Gap:</label>
						<input
							type="number"
							value={ticksGap}
							onChange={(e) => !isTicksGapLocked && setTicksGap(Number(e.target.value))}
							disabled={isTicksGapLocked}
						/>
					</div>
				</div>
			</div>
		</div>
	);
}

export default Controls;
