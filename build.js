const fs = require('fs');
const path = require('path');
console.log('Building WordPress plugin...');

// Ensure the plugin directory structure is correct
const pluginDir = 'wp-site-migrator';
if (!fs.existsSync(pluginDir)) {
    console.error('Plugin directory not found!');
    process.exit(1);
}

// Check required files
const requiredFiles = [
    'wp-site-migrator.php',
    'assets/admin.css',
    'assets/admin.js',
    'templates/installer.php',
    'readme.txt'
];
let allFilesExist = true;

requiredFiles.forEach(file => {
    const filePath = path.join(pluginDir, file);
    if (!fs.existsSync(filePath)) {
        console.error(`Required file missing: ${file}`);
        allFilesExist = false;
    }
});

if (!allFilesExist) {
    console.error('Build failed: Missing required files');
    process.exit(1);
}

console.log('✓ All required files present');
console.log('✓ Plugin structure validated');
console.log('✓ Build completed successfully');

console.log('\nPlugin is ready for packaging!');
console.log('Run "npm run zip" to create the distribution package.');
