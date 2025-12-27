# WEDEV Media Extension

Reusable media handling components for PrestaShop modules.

## Features

- **Dropzone**: Drag-and-drop file uploads
- **Lightbox**: Image viewing overlay
- **Image Preview**: Instant preview on file select
- **Video Parsing**: YouTube/Vimeo URL detection and embedding
- **File Utilities**: Size formatting, MIME icons

## JavaScript API

### Dropzone

```javascript
// Initialize a dropzone
WedevMedia.initDropzone(element, {
    onFile: (file, input) => console.log(file.name)
});

// Auto-init all dropzones
WedevMedia.initAllDropzones(container);
```

### Lightbox

```javascript
// Open lightbox
WedevMedia.openLightbox('https://example.com/image.jpg');

// Close lightbox
WedevMedia.closeLightbox();
```

HTML trigger:
```html
<img src="thumb.jpg" data-lightbox="full.jpg">
```

### Image Preview

```javascript
// Preview image from input
WedevMedia.previewImage(inputElement, imgElement);
```

### Video Parsing

```javascript
// Parse video URL
const info = WedevMedia.parseVideoUrl('https://youtube.com/watch?v=xxx');
// { source: 'youtube', id: 'xxx', embedUrl: '...', thumbnail: '...' }

// Get embed HTML
const html = WedevMedia.getVideoEmbed('https://youtube.com/watch?v=xxx');
// <div class="ratio ratio-16x9"><iframe>...</iframe></div>
```

### Utilities

```javascript
// Format file size
WedevMedia.formatFileSize(1048576); // "1.0 MB"

// Get Material Icon for MIME type
WedevMedia.getMimeIcon('image/jpeg'); // "image"
WedevMedia.getMimeIcon('application/pdf'); // "picture_as_pdf"
```

## CSS

Minimal CSS is provided only for components Bootstrap doesn't have:
- `.wedev-dropzone` / `.acf-dropzone`
- `.wedev-lightbox` / `.acf-lightbox`

All other styling uses Bootstrap classes.

## Usage in Smarty

```smarty
<div class="wedev-dropzone">
    <input type="file" name="image">
    <div>Drop file here</div>
</div>

<img src="{$thumb}" data-lightbox="{$full_image}">
```

## Dependencies

- Requires **UI Extension** for toast notifications

