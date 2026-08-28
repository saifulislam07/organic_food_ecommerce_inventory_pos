/**
 * TinyMCE, self-hosted: every piece is bundled here rather than fetched from a
 * CDN, so the editor works offline and needs no API key.
 *
 * Nothing imports this file directly at boot — editor.js pulls it in only when
 * a page actually has a rich field, which keeps it out of the main admin chunk.
 */
import tinymce from 'tinymce';

import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';

import 'tinymce/plugins/autolink';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/wordcount';

// The toolbar is part of the page, so its stylesheet is injected normally.
import 'tinymce/skins/ui/oxide/skin.min.css';

// These two live inside the editing iframe, which the page's CSS cannot reach,
// so they are handed to TinyMCE as text.
import contentUiCss from 'tinymce/skins/ui/oxide/content.min.css?inline';
import contentCss from 'tinymce/skins/content/default/content.min.css?inline';

export { contentCss, contentUiCss, tinymce };
