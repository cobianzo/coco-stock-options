import defaultConfig from '@wordpress/scripts/config/webpack.config.js';
import * as glob from 'glob';
import path from 'path';
import fs from 'fs';

const blockJsonFiles = glob.sync('./src/blocks/*/block.json');

const blockEntries = blockJsonFiles.reduce((acc, blockJsonFile) => {
    const blockDir = path.dirname(blockJsonFile);
    const blockName = path.basename(blockDir);

    // Editor script
    const indexScriptPath = path.resolve(blockDir, 'index.ts');
    if (fs.existsSync(indexScriptPath)) {
        acc[`blocks/${blockName}/index`] = indexScriptPath;
    }

    // Frontend script
    const frontendScriptPath = path.resolve(blockDir, 'frontend.tsx');
    if (fs.existsSync(frontendScriptPath)) {
        acc[`blocks/${blockName}/frontend`] = frontendScriptPath;
    }

    return acc;
}, {});

export default {
    ...defaultConfig,
    entry: {
        'index': './src/index.ts',
        'admin': './src/admin.ts',
        ...blockEntries,
    },
};