import React, { useState } from 'react';

/**
 * COMPONENT
 */
function UrlGenerator({ strikeSell, strikeBuy }: { strikeSell: number; strikeBuy: number }) {
	// Internal state vars
	const [generatedUrl, setGeneratedUrl] = useState('');

	// The function helper
	// ===================
	const generateUrl = () => {
		// Get current URL and create URL object
		const currentUrl = new URL(window.location.href);

		// Remove existing strike parameters if they exist
		currentUrl.searchParams.delete('strikesell');
		currentUrl.searchParams.delete('strikebuy');

		// Add new strike parameters
		if (strikeSell) currentUrl.searchParams.set('strikesell', String(strikeSell));
		if (strikeBuy) currentUrl.searchParams.set('strikebuy', String(strikeBuy));

		setGeneratedUrl(currentUrl.toString());
	};

	// Copy to Clipboard fn
	// =======================
	const copyToClipboard = () => {
		navigator.clipboard
			.writeText(generatedUrl)
			.then(() => {
				alert('URL copied to clipboard!');
			})
			.catch((err) => {
				console.error('Failed to copy URL:', err);
			});
	};

	/**
	 * ===========
	 * JSX
	 * ===========
	 */
	return (
		<div className="url-generator">
			<button onClick={generateUrl} className="generate-button">
				Create link
			</button>

			{generatedUrl && (
				<div className="url-display">
					<input type="text" readOnly value={generatedUrl} className="url-input" />
					<button onClick={copyToClipboard} className="copy-button">
						Copy
					</button>
				</div>
			)}
		</div>
	);
}

export default UrlGenerator;
