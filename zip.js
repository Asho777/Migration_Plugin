const fs = require('fs');
const archiver = require('archiver');
const path = require('path');
console.log('Creating WordPress plugin package...');

// Create output directory if it doesn't exist
if (!fs.existsSync('dist')) {
    fs.mkdirSync('dist');
}

// Create a file to stream archive data to
const output = fs.createWriteStream('dist/wp-site-migrator.zip');
const archive = archiver('zip', {
    zlib: { level: 9 } // Sets the compression level
});
// Listen for all archive data to be written
output.on('close', function() {
    console.log('✓ Plugin package created successfully!');
    console.log(`✓ Total size: ${archive.pointer()} bytes`);
    console.log('✓ File: dist/wp-site-migrator.zip');
    console.log('\nInstallation Instructions:');
    console.log('1. Upload wp-site-migrator.zip to WordPress admin');
    console.log('2. Install and activate the plugin');
    console.log('3. Go to Tools > Site Migrator to start using');
});

// Handle warnings
archive.on('warning', function(err) {
    if (err.code === 'ENOENT') {
        console.warn('Warning:', err);
    } else {
        throw err;
    }
});

// Handle errors
archive.on('error', function(err) {
    throw err;
});

// Pipe archive data to the file
archive.pipe(output);

// Add files to the archive with proper WordPress plugin structure
const filesToAdd = [
    'wp-site-migrator.php',
    'readme.txt',
    'index.php'
];
const dirsToAdd = [
    'assets',
    'templates', 
    'languages'
];
// Add individual files
filesToAdd.forEach(file => {
    if (fs.existsSync(file)) {
        archive.file(file, { name: `wp-site-migrator/${file}` });
    }
});

// Add directories
dirsToAdd.forEach(dir => {
    if (fs.existsSync(dir)) {
        archive.directory(dir, `wp-site-migrator/${dir}`);
    }
});

// Finalize the archive
archive.finalize();
