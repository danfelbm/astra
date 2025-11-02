/**
 * Custom Upload Adapter para CKEditor 5
 * Integración con FileUploadController de Laravel
 */

interface UploadedFile {
  id: string;
  name: string;
  size: number;
  path: string;
  url: string;
  mime_type: string;
  uploaded_at: string;
}

interface FileUploadResponse {
  success: boolean;
  files: UploadedFile[];
  message?: string;
}

class LaravelUploadAdapter {
  private loader: any;
  private xhr: XMLHttpRequest | null = null;

  constructor(loader: any) {
    this.loader = loader;
  }

  /**
   * Inicia el proceso de upload
   */
  upload(): Promise<{ default: string }> {
    return this.loader.file.then(
      (file: File) =>
        new Promise<{ default: string }>((resolve, reject) => {
          this._initRequest();
          this._initListeners(resolve, reject, file);
          this._sendRequest(file);
        })
    );
  }

  /**
   * Cancela el upload en progreso
   */
  abort(): void {
    if (this.xhr) {
      this.xhr.abort();
    }
  }

  /**
   * Inicializa el XMLHttpRequest
   */
  private _initRequest(): void {
    const xhr = (this.xhr = new XMLHttpRequest());

    // Usar el endpoint de FileUploadController
    xhr.open('POST', '/admin/api/files/upload', true);
    xhr.responseType = 'json';

    // Agregar CSRF token de Laravel
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content');

    if (csrfToken) {
      xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    }

    // Agregar headers adicionales
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  }

  /**
   * Configura los event listeners del XMLHttpRequest
   */
  private _initListeners(
    resolve: (value: { default: string }) => void,
    reject: (reason?: any) => void,
    file: File
  ): void {
    const xhr = this.xhr!;
    const loader = this.loader;
    const errorMsg = `No se pudo subir el archivo: ${file.name}`;

    // Error de red
    xhr.addEventListener('error', () => reject(errorMsg));

    // Upload cancelado
    xhr.addEventListener('abort', () => reject('Upload cancelado'));

    // Upload completado
    xhr.addEventListener('load', () => {
      const response: FileUploadResponse = xhr.response;

      // Verificar respuesta del servidor
      if (!response || !response.success) {
        return reject(response?.message || errorMsg);
      }

      // CKEditor espera un objeto con la propiedad 'default' que contiene la URL de la imagen
      const uploadedFile = response.files[0];
      resolve({
        default: uploadedFile.url,
      });
    });

    // Progress tracking para mostrar barra de progreso
    if (xhr.upload) {
      xhr.upload.addEventListener('progress', (evt) => {
        if (evt.lengthComputable) {
          loader.uploadTotal = evt.total;
          loader.uploaded = evt.loaded;
        }
      });
    }
  }

  /**
   * Envía la petición con el archivo
   */
  private _sendRequest(file: File): void {
    const data = new FormData();

    // Estructura esperada por FileUploadController
    data.append('files[]', file);
    data.append('field_id', 'email-template-image');
    data.append('module', 'campanas');

    this.xhr!.send(data);
  }
}

/**
 * Factory function para el plugin de CKEditor
 * Este plugin reemplaza el FileRepository adapter por defecto
 */
export function LaravelUploadAdapterPlugin(editor: any): void {
  editor.plugins.get('FileRepository').createUploadAdapter = (loader: any) => {
    return new LaravelUploadAdapter(loader);
  };
}

export default LaravelUploadAdapter;
