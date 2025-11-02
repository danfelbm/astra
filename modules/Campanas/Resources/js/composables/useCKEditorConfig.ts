/**
 * Composable para configuración de CKEditor 5
 * Optimizado para plantillas de email marketing
 */

import {
  ClassicEditor,
  Essentials,
  Paragraph,
  Bold,
  Italic,
  Underline,
  Strikethrough,
  Link,
  List,
  Image,
  ImageToolbar,
  ImageCaption,
  ImageStyle,
  ImageResize,
  ImageUpload,
  Table,
  TableToolbar,
  TableProperties,
  TableCellProperties,
  Heading,
  Font,
  Alignment,
  RemoveFormat,
  Undo,
  SourceEditing,
  GeneralHtmlSupport,
  type EditorConfig,
} from 'ckeditor5';

import { LaravelUploadAdapterPlugin } from '../utils/CustomUploadAdapter';

/**
 * Configuración completa de CKEditor 5 para email marketing
 */
export function useCKEditorConfig() {
  const config: EditorConfig = {
    plugins: [
      // Core
      Essentials,
      Paragraph,
      Undo,

      // Formato de texto
      Bold,
      Italic,
      Underline,
      Strikethrough,
      RemoveFormat,

      // Fuentes
      Font, // Incluye FontFamily, FontSize, FontColor, FontBackgroundColor

      // Alineación
      Alignment,

      // Enlaces
      Link,

      // Listas
      List,

      // Imágenes (con upload)
      Image,
      ImageToolbar,
      ImageCaption,
      ImageStyle,
      ImageResize,
      ImageUpload,

      // Tablas
      Table,
      TableToolbar,
      TableProperties,
      TableCellProperties,

      // Encabezados
      Heading,

      // HTML avanzado
      SourceEditing,
      GeneralHtmlSupport,

      // Upload adapter personalizado
      LaravelUploadAdapterPlugin,
    ],

    toolbar: [
      'undo',
      'redo',
      '|',
      'heading',
      '|',
      'bold',
      'italic',
      'underline',
      'strikethrough',
      '|',
      'fontSize',
      'fontFamily',
      'fontColor',
      'fontBackgroundColor',
      '|',
      'alignment',
      '|',
      'link',
      'uploadImage',
      'insertTable',
      '|',
      'bulletedList',
      'numberedList',
      '|',
      'removeFormat',
      '|',
      'sourceEditing',
    ],

    // Configuración de encabezados
    heading: {
      options: [
        {
          model: 'paragraph',
          title: 'Párrafo',
          class: 'ck-heading_paragraph',
        },
        {
          model: 'heading1',
          view: 'h1',
          title: 'Encabezado 1',
          class: 'ck-heading_heading1',
        },
        {
          model: 'heading2',
          view: 'h2',
          title: 'Encabezado 2',
          class: 'ck-heading_heading2',
        },
        {
          model: 'heading3',
          view: 'h3',
          title: 'Encabezado 3',
          class: 'ck-heading_heading3',
        },
        {
          model: 'heading4',
          view: 'h4',
          title: 'Encabezado 4',
          class: 'ck-heading_heading4',
        },
      ],
    },

    // Configuración de tamaños de fuente
    fontSize: {
      options: [9, 11, 13, 'default', 17, 19, 21, 24, 28, 32, 36],
      supportAllValues: true,
    },

    // Configuración de familias de fuente
    fontFamily: {
      options: [
        'default',
        'Arial, Helvetica, sans-serif',
        'Courier New, Courier, monospace',
        'Georgia, serif',
        'Lucida Sans Unicode, Lucida Grande, sans-serif',
        'Tahoma, Geneva, sans-serif',
        'Times New Roman, Times, serif',
        'Trebuchet MS, Helvetica, sans-serif',
        'Verdana, Geneva, sans-serif',
      ],
      supportAllValues: true,
    },

    // Configuración de colores de texto
    fontColor: {
      colors: [
        { color: '#000000', label: 'Negro' },
        { color: '#424242', label: 'Gris oscuro' },
        { color: '#757575', label: 'Gris' },
        { color: '#BDBDBD', label: 'Gris claro' },
        { color: '#FFFFFF', label: 'Blanco' },
        { color: '#EF4444', label: 'Rojo' },
        { color: '#F97316', label: 'Naranja' },
        { color: '#F59E0B', label: 'Amarillo' },
        { color: '#10B981', label: 'Verde' },
        { color: '#06B6D4', label: 'Cian' },
        { color: '#3B82F6', label: 'Azul' },
        { color: '#8B5CF6', label: 'Morado' },
        { color: '#EC4899', label: 'Rosa' },
      ],
      columns: 5,
    },

    // Configuración de colores de fondo
    fontBackgroundColor: {
      colors: [
        { color: '#FFFFFF', label: 'Blanco' },
        { color: '#F3F4F6', label: 'Gris claro' },
        { color: '#FEE2E2', label: 'Rojo claro' },
        { color: '#FFEDD5', label: 'Naranja claro' },
        { color: '#FEF3C7', label: 'Amarillo claro' },
        { color: '#D1FAE5', label: 'Verde claro' },
        { color: '#CFFAFE', label: 'Cian claro' },
        { color: '#DBEAFE', label: 'Azul claro' },
        { color: '#EDE9FE', label: 'Morado claro' },
        { color: '#FCE7F3', label: 'Rosa claro' },
      ],
      columns: 5,
    },

    // Configuración de tablas
    table: {
      contentToolbar: [
        'tableColumn',
        'tableRow',
        'mergeTableCells',
        'tableProperties',
        'tableCellProperties',
      ],
      tableProperties: {
        borderColors: [
          { color: '#000000' },
          { color: '#E5E7EB' },
          { color: '#3B82F6' },
        ],
        backgroundColors: [
          { color: '#FFFFFF' },
          { color: '#F9FAFB' },
          { color: '#EFF6FF' },
        ],
      },
      tableCellProperties: {
        borderColors: [
          { color: '#000000' },
          { color: '#E5E7EB' },
          { color: '#3B82F6' },
        ],
        backgroundColors: [
          { color: '#FFFFFF' },
          { color: '#F9FAFB' },
          { color: '#EFF6FF' },
        ],
      },
    },

    // Configuración de imágenes
    image: {
      toolbar: [
        'imageTextAlternative',
        'toggleImageCaption',
        '|',
        'imageStyle:inline',
        'imageStyle:block',
        'imageStyle:side',
        '|',
        'resizeImage',
      ],
      resizeOptions: [
        {
          name: 'resizeImage:original',
          label: 'Original',
          value: null,
        },
        {
          name: 'resizeImage:25',
          label: '25%',
          value: '25',
        },
        {
          name: 'resizeImage:50',
          label: '50%',
          value: '50',
        },
        {
          name: 'resizeImage:75',
          label: '75%',
          value: '75',
        },
      ],
      styles: {
        options: ['inline', 'block', 'side'],
      },
    },

    // Configuración de enlaces
    link: {
      decorators: {
        openInNewTab: {
          mode: 'manual',
          label: 'Abrir en nueva pestaña',
          attributes: {
            target: '_blank',
            rel: 'noopener noreferrer',
          },
        },
      },
      addTargetToExternalLinks: true,
    },

    // HTML Support - permite más elementos HTML
    htmlSupport: {
      allow: [
        {
          name: /.*/,
          attributes: true,
          classes: true,
          styles: true,
        },
      ],
    },

    // Placeholder
    placeholder: 'Escribe el contenido de tu plantilla de email aquí...',

    // Licencia GPL
    licenseKey: 'GPL',
  };

  return {
    editor: ClassicEditor,
    config,
  };
}
