/**
 * Rich text for the long fields — page content, product and category
 * descriptions.
 *
 * Markup-driven like the rest of the panel:
 *
 *   <textarea name="content_en" data-editor></textarea>
 *
 * Add `data-editor="basic"` for a shorter toolbar.
 */
const TOOLBARS = {
    full: 'undo redo | blocks | bold italic underline | bullist numlist | link table | '
        + 'alignleft aligncenter alignright | removeformat | searchreplace code fullscreen',
    basic: 'undo redo | bold italic | bullist numlist | link | removeformat',
};

const FIELDS = 'textarea[data-editor]:not([data-editor-ready])';

export async function initEditors(root = document) {
    const fields = Array.from(root.querySelectorAll(FIELDS));

    if (fields.length === 0) {
        return;
    }

    const { contentCss, contentUiCss, tinymce } = await import('./tinymce-bundle.js');

    for (const field of fields) {
        field.dataset.editorReady = 'yes';

        await tinymce.init({
            target: field,
            // Self-hosted under the GPL: no key, no "upgrade" nagging.
            license_key: 'gpl',
            promotion: false,
            branding: false,

            // The stylesheets are bundled, so TinyMCE must not go looking for
            // them over the network.
            skin: false,
            content_css: false,
            content_style: [
                contentCss,
                contentUiCss,
                // Bengali needs a font that actually has the glyphs.
                'body { font-family: system-ui, "Noto Sans Bengali", "Hind Siliguri", sans-serif;'
                + ' font-size: 15px; line-height: 1.7; }',
            ].join('\n'),

            plugins: 'autolink code fullscreen link lists searchreplace table wordcount',
            toolbar: TOOLBARS[field.dataset.editor] || TOOLBARS.full,
            menubar: false,
            statusbar: true,
            height: Number(field.dataset.editorHeight) || 360,

            // Keep the links the admin typed exactly as typed.
            convert_urls: false,
            // Pasting from Word should not drag its markup along.
            paste_as_text: false,
            table_default_attributes: { class: 'table' },

            setup(editor) {
                // The textarea is what gets posted, so keep it in step. TinyMCE
                // syncs on submit, but not when another script reads the value.
                editor.on('change input undo redo', () => editor.save());
            },
        });
    }
}
