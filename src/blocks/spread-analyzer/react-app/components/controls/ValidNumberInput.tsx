import React, { useEffect, useRef, useState } from 'react';

import { isValidValue } from '../../helpers/helpers';

interface ValidNumberInputProps {
	validValues: number[];
	realValue: number;
	onChange: (value: number) => void;
}

/**
 * Lookup component, to select a strike and limit the selectable value to the list of valid strikes
 * This component is a little complex and probalby couldbe improved or simplified. It's nothing but a lookup.
 *
 * The variable that this input modifies is passed with 'realValue',
 * and the way to update it it calling onChange.
 *
 * The internal var inputValue is the value of the input. It matches with realValue, but while editing
 * they can be temprarily different.
 *
 */



const ValidNumberInput = ({ validValues, realValue, onChange }: ValidNumberInputProps) => {

	const [inputValue, setInputValue] = useState<string>(realValue?.toFixed(3) || (validValues.length > 0 ? validValues[0]?.toFixed(3) : '0.000'));
	const [showSuggestions, setShowSuggestions] = useState(false);
	const inputRef = useRef<HTMLDivElement>(null);

	useEffect(() => {
		const validValue = isValidValue(validValues, realValue, true );
		if (typeof validValue === 'string' && validValue) {
			setInputValue(validValue);
		}
	}, [realValue]);

	const updateValue = (newValue: string | number) => {
		const valueToValidate = typeof newValue === 'string' ? parseFloat(newValue) : newValue;
		const validValue: string | boolean = isValidValue(validValues, valueToValidate, true );
		let validValueNumber = typeof validValue === 'string' ? parseFloat(validValue) : validValue;
		if (typeof validValueNumber === 'number') {
			if ( onChange ) {
				onChange(validValueNumber as number);
			}
		}
	};

	const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		const value = e.target.value;
		if (/^\d*\.?\d*$/.test(value) || value === '') {
			setInputValue(value); // update the value visible on the input.
			setShowSuggestions(true);
		}
	};

	const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
		if (e.key === 'Enter') {
			e.preventDefault();
			const suggestedValues = getSuggestedValues();
			if (suggestedValues.length > 0) {
				updateValue(suggestedValues[0]);
				setShowSuggestions(false);
			}
			return;
		}

		if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
			e.preventDefault();
			const currentValue = parseFloat(inputValue) || validValues[0];
			let index = validValues.findIndex((val) => val === currentValue);

			if (index === -1) {
				index = validValues.findIndex((val) => val > currentValue);
				if (index === -1) index = validValues.length - 1;
			}

			if (e.key === 'ArrowUp' && index < validValues.length - 1) {
				updateValue(validValues[index + 1]);
			} else if (e.key === 'ArrowDown' && index > 0) {
				updateValue(validValues[index - 1]);
			}
		}
	};

	const handleBlur = (e: React.FocusEvent) => {
		// Check if the related target is within our component
		if (!inputRef.current?.contains(e.relatedTarget as Node)) {
			setShowSuggestions(false);
			const currentValue = parseFloat(inputValue);
			if (isNaN(currentValue)) {
				updateValue(validValues[0]);
				return;
			}

			if (validValues.includes(currentValue)) {
				updateValue(currentValue);
				return;
			}

			const closestValue = validValues.reduce((prev, curr) => (Math.abs(curr - currentValue) < Math.abs(prev - currentValue) ? curr : prev));
			updateValue(closestValue);
		}
	};

	const handleFocus = () => {
		setShowSuggestions(true);
	};

	const getSuggestedValues = () => {
		const currentValue = parseFloat(inputValue) || 0;
		return validValues.filter((val) => val.toString().includes(currentValue.toString())).slice(0, 5);
	};

	const handleSuggestionClick = (value: string | number) => {
		const valueNumber = typeof value === 'string' ? parseFloat(value) : value;

		updateValue(valueNumber);
		// if ( onChange ) {
		// 	onChange(valueNumber);
		// }
		setShowSuggestions(false);
	};

	// Styles
	const styles = {
		container: {
			position: 'relative' as const,
			display: 'inline-block',
		},
		suggestions: {
			position: 'absolute' as const,
			top: '100%',
			left: 0,
			zIndex: 1000,
			backgroundColor: '#fff',
			border: '1px solid #ddd',
			borderRadius: '4px',
			boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
			width: '100%',
			maxHeight: '150px',
			overflowY: 'auto' as const,
			display: showSuggestions ? 'block' : 'none',
		},
		suggestion: {
			padding: '8px 12px',
			cursor: 'pointer',
			':hover': {
				backgroundColor: '#f5f5f5',
			},
		},
	};

	return (
		<div ref={inputRef} style={styles.container}>
			<input type="text" value={inputValue} onChange={handleChange} onKeyDown={handleKeyDown} onBlur={handleBlur} onFocus={handleFocus} />
			<ul style={styles.suggestions} className="valid-number-input-suggestions">
				{getSuggestedValues().map((value) => (
					<li key={value} style={styles.suggestion} onClick={() =>{
						handleSuggestionClick(value); }}>
						{value.toFixed(3)}
					</li>
				))}
			</ul>
		</div>
	);
};

export default ValidNumberInput;
