import React, { useState, useRef, useEffect } from 'react';

interface ValidNumberInputProps {
  validValues: number[];
	defaultValue: number;
	setDefaultValue: CallableFunction;
  onChange?: (value: number) => void;
}

const ValidNumberInput = ({ validValues, defaultValue, setDefaultValue, onChange }: ValidNumberInputProps) => {
  const [inputValue, setInputValue] = useState(defaultValue?.toFixed(3) || (validValues.length > 0 ? validValues[0]?.toFixed(3) : '0.000'));
  const [showSuggestions, setShowSuggestions] = useState(false);
  const inputRef = useRef<HTMLDivElement>(null);

	useEffect(() => {
		setInputValue(defaultValue?.toFixed(3) || (validValues.length > 0 ? validValues[0]?.toFixed(3) : '0.000'));
	}, [defaultValue]);


  const updateValue = (newValue: number | string) => {
    setDefaultValue( parseFloat(newValue as string) );
    if (onChange && typeof newValue === 'number') {
      onChange(newValue);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    if (/^\d*\.?\d*$/.test(value) || value === '') {
      updateValue(value);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
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

      const closestValue = validValues.reduce((prev, curr) =>
        Math.abs(curr - currentValue) < Math.abs(prev - currentValue) ? curr : prev
      );
      updateValue(closestValue);
    }
  };

  const handleFocus = () => {
    setShowSuggestions(true);
  };

  const getSuggestedValues = () => {
    const currentValue = parseFloat(inputValue) || 0;
    return validValues
      .filter(val => val.toString().includes(currentValue.toString()))
      .slice(0, 5);
  };

  const handleSuggestionClick = (value: number) => {
    updateValue(value);
    setShowSuggestions(false);
  };

  // Styles
  const styles = {
    container: {
      position: 'relative' as const,
      display: 'inline-block'
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
      display: showSuggestions ? 'block' : 'none'
    },
    suggestion: {
      padding: '8px 12px',
      cursor: 'pointer',
      ':hover': {
        backgroundColor: '#f5f5f5'
      }
    }
  };

  return (
    <div ref={inputRef} style={styles.container}>
      <input
        type="text"
        value={inputValue}
        onChange={handleChange}
        onKeyDown={handleKeyDown}
        onBlur={handleBlur}
        onFocus={handleFocus}
      />
      <div style={styles.suggestions}>
        {getSuggestedValues().map((value) => (
          <div
            key={value}
            style={styles.suggestion}
            onClick={() => handleSuggestionClick(value)}
          >
            {value.toFixed(3)}
          </div>
        ))}
      </div>
    </div>
  );
};

export default ValidNumberInput;