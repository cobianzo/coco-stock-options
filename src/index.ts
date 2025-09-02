/**
 * Main entry point for Coco Stock Options plugin
 *
 * @package CocoStockOptions
 */

// Import admin functionality
import './admin';
import './style.css';


// Import block functionality
import './blocks/example-block';

// Import utilities
import * as utils from './utils';

// Make utilities available globally for debugging or specific integrations
// @ts-ignore
window.cocoStockOptionsUtils = utils;

console.log('Coco Stock Options plugin loaded.');