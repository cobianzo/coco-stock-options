import defaultConfig from '@wordpress/scripts/config/webpack.config.js';
import path from 'path';

export default {
    ...defaultConfig,
    entry: {
        'index': './src/index.ts',
        'admin': './src/admin.ts',
        'blocks/example-block/index': './src/blocks/example-block/index.ts',
    },
};
