/**
 * Converts an image file to WebP format and compresses it to be under the target size (1.99MB by default)
 * 
 * @param file - The original image file
 * @param maxSizeBytes - Maximum file size in bytes (default: 1.99MB)
 * @param maxQuality - Maximum quality (0-1, default: 0.92)
 * @param minQuality - Minimum quality (0-1, default: 0.5)
 * @returns Promise resolving to a File object in WebP format, or null if conversion fails
 */
export async function convertToWebP(
    file: File,
    maxSizeBytes: number = 1.99 * 1024 * 1024, // 1.99MB default
    maxQuality: number = 0.92,
    minQuality: number = 0.5
): Promise<File | null> {
    return new Promise((resolve, reject) => {
        // Check if file is already small enough
        if (file.size <= maxSizeBytes && file.type === 'image/webp') {
            resolve(file);
            return;
        }

        // Check if file is an image
        if (!file.type.startsWith('image/')) {
            reject(new Error('File is not an image'));
            return;
        }

        const reader = new FileReader();
        
        reader.onload = async (e) => {
            try {
                const img = new Image();
                img.src = e.target?.result as string;

                await new Promise<void>((imgResolve, imgReject) => {
                    img.onload = () => imgResolve();
                    img.onerror = () => imgReject(new Error('Failed to load image'));
                });

                // Create canvas with original dimensions
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');

                if (!ctx) {
                    reject(new Error('Failed to get canvas context'));
                    return;
                }

                // Draw image to canvas
                ctx.drawImage(img, 0, 0);

                // Helper to convert canvas to blob
                const canvasToBlob = (canvas: HTMLCanvasElement, quality: number): Promise<Blob> => {
                    return new Promise((blobResolve, blobReject) => {
                        try {
                            canvas.toBlob(
                                (b) => {
                                    if (b) {
                                        blobResolve(b);
                                    } else {
                                        blobReject(new Error('Failed to convert canvas to blob'));
                                    }
                                },
                                'image/webp',
                                quality
                            );
                        } catch (error) {
                            blobReject(error);
                        }
                    });
                };

                // Binary search for optimal quality
                let quality = maxQuality;
                let blob: Blob | null = null;
                let currentSize = Infinity;

                // Start with max quality and reduce if needed
                while (quality >= minQuality && currentSize > maxSizeBytes) {
                    try {
                        blob = await canvasToBlob(canvas, quality);
                        currentSize = blob.size;

                        // If still too large, reduce quality
                        if (currentSize > maxSizeBytes) {
                            quality -= 0.1;
                        } else {
                            break;
                        }
                    } catch (error) {
                        reject(new Error('Failed to convert image to WebP'));
                        return;
                    }
                }

                // If still too large, try reducing dimensions
                if (currentSize > maxSizeBytes && blob) {
                    const scaleFactor = Math.sqrt(maxSizeBytes / currentSize);
                    const newWidth = Math.floor(img.width * scaleFactor);
                    const newHeight = Math.floor(img.height * scaleFactor);

                    canvas.width = newWidth;
                    canvas.height = newHeight;
                    ctx.drawImage(img, 0, 0, newWidth, newHeight);

                    try {
                        blob = await canvasToBlob(canvas, 0.85); // Use slightly lower quality when resizing
                    } catch (error) {
                        reject(new Error('Failed to convert resized image to WebP'));
                        return;
                    }
                }

                if (!blob) {
                    reject(new Error('Failed to convert image to WebP'));
                    return;
                }

                // Create a new File object with WebP extension
                const fileName = file.name.replace(/\.[^/.]+$/, '') + '.webp';
                const webpFile = new File([blob], fileName, {
                    type: 'image/webp',
                    lastModified: Date.now()
                });

                resolve(webpFile);
            } catch (error) {
                reject(error);
            }
        };

        reader.onerror = () => {
            reject(new Error('Failed to read file'));
        };

        reader.readAsDataURL(file);
    });
}

/**
 * Processes multiple image files, converting them to WebP format if needed
 * 
 * @param files - Array of image files to process
 * @param maxSizeBytes - Maximum file size in bytes (default: 1.99MB)
 * @param onProgress - Optional callback for progress updates
 * @returns Promise resolving to array of processed File objects
 */
export async function processImagesForUpload(
    files: File[],
    maxSizeBytes: number = 1.99 * 1024 * 1024, // 1.99MB default
    onProgress?: (processed: number, total: number) => void
): Promise<File[]> {
    const processedFiles: File[] = [];

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        try {
            let processedFile: File;

            // If file is already under size and is WebP, use as-is
            if (file.size <= maxSizeBytes && file.type === 'image/webp') {
                processedFile = file;
            } else {
                // Convert to WebP and compress
                const converted = await convertToWebP(file, maxSizeBytes);
                if (!converted) {
                    console.warn(`Failed to convert ${file.name}, skipping`);
                    continue;
                }
                processedFile = converted;

                // Log compression stats
                const originalSizeMB = (file.size / 1024 / 1024).toFixed(2);
                const newSizeMB = (processedFile.size / 1024 / 1024).toFixed(2);
                console.log(
                    `Converted ${file.name}: ${originalSizeMB}MB → ${newSizeMB}MB (${processedFile.type})`
                );
            }

            processedFiles.push(processedFile);
            
            if (onProgress) {
                onProgress(i + 1, files.length);
            }
        } catch (error) {
            console.error(`Error processing ${file.name}:`, error);
            // Continue with other files even if one fails
            // Optionally, you could add the original file as fallback
        }
    }

    return processedFiles;
}

