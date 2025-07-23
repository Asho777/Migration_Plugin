const fs = require('fs');
const path = require('path');
console.log('Building WordPress plugin...');

// Check required files
const requiredFiles = [
    'wp-site-migrator.php',
    'assets/admin.css',
    'assets/admin.js',
    'templates/installer.php',
    'readme.txt',
    'index.php'
];
let allFilesExist = true;

requiredFiles.forEach(file => {
    if (!fs.existsSync(file)) {
        console.error(`Required file missing: ${file}`);
        allFilesExist = false;
    }
});

if (!allFilesExist) {
    console.error('Build failed: Missing required files');
    process.exit(1);
}

// Check directory structure
const requiredDirs = [
    'assets',
    'templates',
    'languages'
];
requiredDirs.forEach(dir => {
    if (!fs.existsSync(dir)) {
        console.error(`Required directory missing: ${dir}`);
        allFilesExist = false;
    }
});

if (!allFilesExist) {
    console.error('Build failed: Missing required directories');
    process.exit(1);
}

console.log('✓ All required files present');
console.log('✓ All required directories present');
console.log('✓ Plugin structure validated');
console.log('✓ Build completed successfully');

console.log('\nPlugin is ready for packaging!');
console.log('Run "npm run zip" to create the distribution package.');
