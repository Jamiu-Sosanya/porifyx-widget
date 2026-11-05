<?php
/**
 * Generate PWA icons
 * Run: php scripts/generate-pwa-icons.php
 */

// Create directory if it doesn't exist
$imagesDir = __DIR__ . '/../public/images';
if (!is_dir($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

// Icon sizes to create
$sizes = [192, 512];

// Brand colors
$primaryColor = '#6366f1'; // Indigo
$secondaryColor = '#ffffff'; // White

// Convert hex to RGB
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return [$r, $g, $b];
}

echo "Generating PWA icons...\n";

foreach ($sizes as $size) {
    // Create image
    $img = imagecreatetruecolor($size, $size);
    
    // Allocate colors
    [$r, $g, $b] = hexToRgb($primaryColor);
    $primaryRgb = imagecolorallocate($img, $r, $g, $b);
    
    [$r, $g, $b] = hexToRgb($secondaryColor);
    $secondaryRgb = imagecolorallocate($img, $r, $g, $b);
    
    // Fill with primary color
    imagefilledrectangle($img, 0, 0, $size, $size, $primaryRgb);
    
    // Add a square with white background in the center
    $padding = (int)($size * 0.15);
    imagefilledrectangle($img, $padding, $padding, 
        $size - $padding, $size - $padding, $secondaryRgb);
    
    // Save the image
    $filename = "{$imagesDir}/icon-{$size}x{$size}.png";
    imagepng($img, $filename);
    imagedestroy($img);
    
    echo "✓ Created {$size}x{$size} icon: {$filename}\n";
}

// Create maskable versions
foreach ($sizes as $size) {
    // For maskable icons, create a simple design that works well when masked
    $img = imagecreatetruecolor($size, $size);
    
    // Create transparent image
    $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefill($img, 0, 0, $transparent);
    imagesavealpha($img, true);
    
    // Allocate colors
    [$r, $g, $b] = hexToRgb($primaryColor);
    $primaryRgb = imagecolorallocate($img, $r, $g, $b);
    
    // Draw a filled square in the center (safe zone for masking)
    $padding = (int)($size * 0.2);
    imagefilledrectangle($img, $padding, $padding, 
        $size - $padding, $size - $padding, $primaryRgb);
    
    // Save the image
    $filename = "{$imagesDir}/icon-maskable-{$size}x{$size}.png";
    imagepng($img, $filename);
    imagedestroy($img);
    
    echo "✓ Created maskable {$size}x{$size} icon: {$filename}\n";
}

echo "\n✨ PWA icons generated successfully!\n";
echo "Note: You should replace these placeholder icons with your actual app logo.\n";
echo "Required icon dimensions:\n";
echo "  - icon-192x192.png (app icon)\n";
echo "  - icon-512x512.png (app splash screen)\n";
echo "  - icon-maskable-192x192.png (device-specific icon)\n";
echo "  - icon-maskable-512x512.png (device-specific icon)\n";
