/**
 * TipTap WYSIWYG Editor for Laravel Admin
 * Full-featured editor with text formatting, images, links, and more
 */

// Import TipTap modules from CDN
import { Editor } from 'https://esm.sh/@tiptap/core@2.1.13';
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2.1.13';
import Image from 'https://esm.sh/@tiptap/extension-image@2.1.13';
import Link from 'https://esm.sh/@tiptap/extension-link@2.1.13';
import Underline from 'https://esm.sh/@tiptap/extension-underline@2.1.13';
import TextAlign from 'https://esm.sh/@tiptap/extension-text-align@2.1.13';
import Placeholder from 'https://esm.sh/@tiptap/extension-placeholder@2.1.13';
import TextStyle from 'https://esm.sh/@tiptap/extension-text-style@2.1.13';
import Color from 'https://esm.sh/@tiptap/extension-color@2.1.13';

class TipTapAdmin {
    constructor(options) {
        this.element = document.querySelector(options.element);
        this.textarea = document.querySelector(options.textarea);
        this.uploadUrl = options.uploadUrl || '/admin/upload-image';
        this.csrfToken = options.csrfToken || '';
        
        if (!this.element) {
            console.error('TipTap: Element not found');
            return;
        }

        this.init();
    }

    init() {
        // Create toolbar
        this.createToolbar();
        
        // Create editor container
        this.editorContainer = document.createElement('div');
        this.editorContainer.className = 'tiptap-content';
        this.element.appendChild(this.editorContainer);

        // Initialize TipTap
        this.editor = new Editor({
            element: this.editorContainer,
            extensions: [
                StarterKit.configure({
                    heading: {
                        levels: [1, 2, 3, 4],
                    },
                }),
                Underline,
                TextStyle,
                Color,
                Image.configure({
                    inline: false,
                    allowBase64: true,
                    HTMLAttributes: {
                        class: 'editor-image',
                    },
                }),
                Link.configure({
                    openOnClick: false,
                    HTMLAttributes: {
                        target: '_blank',
                        rel: 'noopener noreferrer',
                    },
                }),
                TextAlign.configure({
                    types: ['heading', 'paragraph'],
                }),
                Placeholder.configure({
                    placeholder: 'Start writing your content...',
                }),
            ],
            content: this.textarea ? this.textarea.value : '',
            onUpdate: ({ editor }) => {
                if (this.textarea) {
                    this.textarea.value = editor.getHTML();
                }
                this.updateToolbarState();
            },
            onSelectionUpdate: () => {
                this.updateToolbarState();
            },
        });

        // Hide original textarea
        if (this.textarea) {
            this.textarea.style.display = 'none';
        }
    }

    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'tiptap-toolbar';
        toolbar.innerHTML = `
            <!-- Text Formatting -->
            <div class="toolbar-group">
                <button type="button" data-action="bold" title="Bold (Ctrl+B)">
                    <i class="fas fa-bold"></i>
                </button>
                <button type="button" data-action="italic" title="Italic (Ctrl+I)">
                    <i class="fas fa-italic"></i>
                </button>
                <button type="button" data-action="underline" title="Underline (Ctrl+U)">
                    <i class="fas fa-underline"></i>
                </button>
                <button type="button" data-action="strike" title="Strikethrough">
                    <i class="fas fa-strikethrough"></i>
                </button>
            </div>

            <div class="toolbar-divider"></div>

            <!-- Headings -->
            <div class="toolbar-group">
                <select data-action="heading" title="Heading">
                    <option value="p">Paragraph</option>
                    <option value="1">Heading 1</option>
                    <option value="2">Heading 2</option>
                    <option value="3">Heading 3</option>
                    <option value="4">Heading 4</option>
                </select>
            </div>

            <div class="toolbar-divider"></div>

            <!-- Text Alignment -->
            <div class="toolbar-group">
                <button type="button" data-action="alignLeft" title="Align Left">
                    <i class="fas fa-align-left"></i>
                </button>
                <button type="button" data-action="alignCenter" title="Align Center">
                    <i class="fas fa-align-center"></i>
                </button>
                <button type="button" data-action="alignRight" title="Align Right">
                    <i class="fas fa-align-right"></i>
                </button>
                <button type="button" data-action="alignJustify" title="Justify">
                    <i class="fas fa-align-justify"></i>
                </button>
            </div>

            <div class="toolbar-divider"></div>

            <!-- Lists -->
            <div class="toolbar-group">
                <button type="button" data-action="bulletList" title="Bullet List">
                    <i class="fas fa-list-ul"></i>
                </button>
                <button type="button" data-action="orderedList" title="Numbered List">
                    <i class="fas fa-list-ol"></i>
                </button>
                <button type="button" data-action="blockquote" title="Quote">
                    <i class="fas fa-quote-right"></i>
                </button>
            </div>

            <div class="toolbar-divider"></div>

            <!-- Links & Media -->
            <div class="toolbar-group">
                <button type="button" data-action="link" title="Insert Link">
                    <i class="fas fa-link"></i>
                </button>
                <button type="button" data-action="unlink" title="Remove Link">
                    <i class="fas fa-unlink"></i>
                </button>
                <button type="button" data-action="image" title="Insert Image">
                    <i class="fas fa-image"></i>
                </button>
            </div>

            <div class="toolbar-divider"></div>

            <!-- Code & Special -->
            <div class="toolbar-group">
                <button type="button" data-action="code" title="Inline Code">
                    <i class="fas fa-code"></i>
                </button>
                <button type="button" data-action="codeBlock" title="Code Block">
                    <i class="fas fa-file-code"></i>
                </button>
                <button type="button" data-action="horizontalRule" title="Horizontal Line">
                    <i class="fas fa-minus"></i>
                </button>
            </div>

            <div class="toolbar-divider"></div>

            <!-- Undo/Redo -->
            <div class="toolbar-group">
                <button type="button" data-action="undo" title="Undo (Ctrl+Z)">
                    <i class="fas fa-undo"></i>
                </button>
                <button type="button" data-action="redo" title="Redo (Ctrl+Y)">
                    <i class="fas fa-redo"></i>
                </button>
            </div>

            <div class="toolbar-divider"></div>

            <!-- Clear Formatting -->
            <div class="toolbar-group">
                <button type="button" data-action="clearFormat" title="Clear Formatting">
                    <i class="fas fa-eraser"></i>
                </button>
            </div>
        `;

        this.element.appendChild(toolbar);
        this.toolbar = toolbar;
        this.bindToolbarEvents();
    }

    bindToolbarEvents() {
        // Button clicks
        this.toolbar.querySelectorAll('button[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const action = btn.dataset.action;
                this.executeAction(action);
            });
        });

        // Heading select
        const headingSelect = this.toolbar.querySelector('select[data-action="heading"]');
        if (headingSelect) {
            headingSelect.addEventListener('change', (e) => {
                const value = e.target.value;
                if (value === 'p') {
                    this.editor.chain().focus().setParagraph().run();
                } else {
                    this.editor.chain().focus().toggleHeading({ level: parseInt(value) }).run();
                }
            });
        }
    }

    executeAction(action) {
        const chain = this.editor.chain().focus();

        switch (action) {
            case 'bold':
                chain.toggleBold().run();
                break;
            case 'italic':
                chain.toggleItalic().run();
                break;
            case 'underline':
                chain.toggleUnderline().run();
                break;
            case 'strike':
                chain.toggleStrike().run();
                break;
            case 'alignLeft':
                chain.setTextAlign('left').run();
                break;
            case 'alignCenter':
                chain.setTextAlign('center').run();
                break;
            case 'alignRight':
                chain.setTextAlign('right').run();
                break;
            case 'alignJustify':
                chain.setTextAlign('justify').run();
                break;
            case 'bulletList':
                chain.toggleBulletList().run();
                break;
            case 'orderedList':
                chain.toggleOrderedList().run();
                break;
            case 'blockquote':
                chain.toggleBlockquote().run();
                break;
            case 'code':
                chain.toggleCode().run();
                break;
            case 'codeBlock':
                chain.toggleCodeBlock().run();
                break;
            case 'horizontalRule':
                chain.setHorizontalRule().run();
                break;
            case 'undo':
                chain.undo().run();
                break;
            case 'redo':
                chain.redo().run();
                break;
            case 'clearFormat':
                chain.unsetAllMarks().clearNodes().run();
                break;
            case 'link':
                this.insertLink();
                break;
            case 'unlink':
                chain.unsetLink().run();
                break;
            case 'image':
                this.insertImage();
                break;
        }
    }

    insertLink() {
        const previousUrl = this.editor.getAttributes('link').href || '';
        const url = prompt('Enter URL:', previousUrl);
        
        if (url === null) return;
        
        if (url === '') {
            this.editor.chain().focus().unsetLink().run();
        } else {
            this.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        }
    }

    insertImage() {
        // Create modal for image insertion
        const modal = document.createElement('div');
        modal.className = 'tiptap-modal';
        modal.innerHTML = `
            <div class="tiptap-modal-content">
                <div class="tiptap-modal-header">
                    <h5>Insert Image</h5>
                    <button type="button" class="close-btn">&times;</button>
                </div>
                <div class="tiptap-modal-body">
                    <div class="tabs">
                        <button type="button" class="tab-btn active" data-tab="url">URL</button>
                        <button type="button" class="tab-btn" data-tab="upload">Upload</button>
                    </div>
                    <div class="tab-content active" id="tab-url">
                        <input type="text" id="image-url" class="form-control" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="tab-content" id="tab-upload">
                        <input type="file" id="image-file" class="form-control" accept="image/*">
                        <div id="upload-preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="tiptap-modal-footer">
                    <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                    <button type="button" class="btn btn-primary insert-btn">Insert</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Tab switching
        modal.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                modal.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                modal.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                modal.querySelector(`#tab-${btn.dataset.tab}`).classList.add('active');
            });
        });

        // File preview
        const fileInput = modal.querySelector('#image-file');
        const preview = modal.querySelector('#upload-preview');
        let uploadedDataUrl = null;

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    uploadedDataUrl = ev.target.result;
                    preview.innerHTML = `<img src="${uploadedDataUrl}" style="max-width:100%;max-height:200px;">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Close modal
        const closeModal = () => {
            modal.remove();
        };

        modal.querySelector('.close-btn').addEventListener('click', closeModal);
        modal.querySelector('.cancel-btn').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Insert image
        modal.querySelector('.insert-btn').addEventListener('click', () => {
            const activeTab = modal.querySelector('.tab-btn.active').dataset.tab;
            let imageUrl = '';

            if (activeTab === 'url') {
                imageUrl = modal.querySelector('#image-url').value;
            } else if (uploadedDataUrl) {
                imageUrl = uploadedDataUrl;
            }

            if (imageUrl) {
                this.editor.chain().focus().setImage({ src: imageUrl }).run();
            }
            closeModal();
        });
    }

    updateToolbarState() {
        if (!this.editor) return;

        // Update button active states
        const buttons = {
            bold: this.editor.isActive('bold'),
            italic: this.editor.isActive('italic'),
            underline: this.editor.isActive('underline'),
            strike: this.editor.isActive('strike'),
            bulletList: this.editor.isActive('bulletList'),
            orderedList: this.editor.isActive('orderedList'),
            blockquote: this.editor.isActive('blockquote'),
            code: this.editor.isActive('code'),
            codeBlock: this.editor.isActive('codeBlock'),
            link: this.editor.isActive('link'),
            alignLeft: this.editor.isActive({ textAlign: 'left' }),
            alignCenter: this.editor.isActive({ textAlign: 'center' }),
            alignRight: this.editor.isActive({ textAlign: 'right' }),
            alignJustify: this.editor.isActive({ textAlign: 'justify' }),
        };

        Object.entries(buttons).forEach(([action, isActive]) => {
            const btn = this.toolbar.querySelector(`button[data-action="${action}"]`);
            if (btn) {
                btn.classList.toggle('active', isActive);
            }
        });

        // Update heading select
        const headingSelect = this.toolbar.querySelector('select[data-action="heading"]');
        if (headingSelect) {
            if (this.editor.isActive('heading', { level: 1 })) {
                headingSelect.value = '1';
            } else if (this.editor.isActive('heading', { level: 2 })) {
                headingSelect.value = '2';
            } else if (this.editor.isActive('heading', { level: 3 })) {
                headingSelect.value = '3';
            } else if (this.editor.isActive('heading', { level: 4 })) {
                headingSelect.value = '4';
            } else {
                headingSelect.value = 'p';
            }
        }
    }

    getHTML() {
        return this.editor ? this.editor.getHTML() : '';
    }

    setContent(content) {
        if (this.editor) {
            this.editor.commands.setContent(content);
        }
    }

    destroy() {
        if (this.editor) {
            this.editor.destroy();
        }
    }
}

// Export for use
window.TipTapAdmin = TipTapAdmin;



