/**
 * Type declarations for pdfjs-dist
 *
 * Minimal declarations for the PDF.js library used in PaginatedPDFContent.vue
 */

declare module 'pdfjs-dist' {
    export interface PDFDocumentProxy {
        numPages: number;
        getPage(pageNumber: number): Promise<PDFPageProxy>;
    }

    export interface PDFPageProxy {
        getViewport(params: { scale: number }): PDFPageViewport;
        render(params: PDFRenderParams): PDFRenderTask;
    }

    export interface PDFPageViewport {
        width: number;
        height: number;
    }

    export interface PDFRenderParams {
        canvasContext: CanvasRenderingContext2D;
        viewport: PDFPageViewport;
    }

    export interface PDFRenderTask {
        promise: Promise<void>;
    }

    export interface PDFDocumentLoadingTask {
        promise: Promise<PDFDocumentProxy>;
    }

    export const GlobalWorkerOptions: {
        workerSrc: string;
    };

    export function getDocument(src: string | ArrayBuffer | Uint8Array): PDFDocumentLoadingTask;
}
