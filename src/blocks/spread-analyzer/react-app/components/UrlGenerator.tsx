import React, { useState } from 'react'

function UrlGenerator({
  ticker,
  strikeSell,
  strikeBuy,
}: {
  ticker: string;
  strikeSell: string;
  strikeBuy: string;
}) {
  const [generatedUrl, setGeneratedUrl] = useState('')

  const generateUrl = () => {
    // Get current URL and create URL object
    const currentUrl = new URL(window.location.href)

    // Remove existing strike parameters if they exist
    currentUrl.searchParams.delete('strikeSell')
    currentUrl.searchParams.delete('strikeBuy')

    // Add new strike parameters
    if (strikeSell) currentUrl.searchParams.set('strikeSell', strikeSell)
    if (strikeBuy) currentUrl.searchParams.set('strikeBuy', strikeBuy)

    setGeneratedUrl(currentUrl.toString())
  }

  const copyToClipboard = () => {
    navigator.clipboard.writeText(generatedUrl)
      .then(() => {
        alert('URL copied to clipboard!')
      })
      .catch(err => {
        console.error('Failed to copy URL:', err)
      })
  }

  return (
    <div className="url-generator">
      <button
        onClick={generateUrl}
        className="generate-button"
      >
        Create link
      </button>

      {generatedUrl && (
        <div className="url-display">
          <input
            type="text"
            readOnly
            value={generatedUrl}
            className="url-input"
          />
          <button
            onClick={copyToClipboard}
            className="copy-button"
          >
            Copy
          </button>
        </div>
      )}
    </div>
  )
}

export default UrlGenerator