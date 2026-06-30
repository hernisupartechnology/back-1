<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Receipt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Servicio de Recibos.
 *
 * Maneja la subida segura de archivos, validación de MIME real,
 * generación de thumbnails con Intervention Image y descarga en ZIP.
 */
class ReceiptService
{
    /**
     * Subir un recibo y guardarlo en storage privado.
     *
     * @param UploadedFile $archivo  Archivo subido
     * @param Expense|Income|null $modelo  Modelo al que se adjunta
     * @param int $userId  ID del usuario que sube el recibo
     * @param int $householdId  ID del hogar (para la ruta)
     * @param int $year
     * @param int $month
     * @param string|null $descripcion
     */
    public function subir(
        UploadedFile $archivo,
        mixed $modelo,
        int $userId,
        int $householdId,
        int $year,
        int $month,
        ?string $descripcion = null
    ): Receipt {
        // Validar MIME real con finfo (no confiar en la extensión)
        $mimeReal = $this->detectarMime($archivo);
        $esImagen = in_array($mimeReal, ['image/jpeg', 'image/png', 'image/webp']);
        $esPdf    = $mimeReal === 'application/pdf';

        if (! $esImagen && ! $esPdf) {
            throw new \InvalidArgumentException('Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, WebP) o PDF.');
        }

        // Generar nombre seguro: slug + UUID + extensión
        $extension   = $archivo->extension();
        $nombreSeguro = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME))
            . '_' . Str::uuid() . '.' . $extension;

        // Ruta: receipts/{householdId}/{year}/{month}/{userId}/
        $directorio = "receipts/{$householdId}/{$year}/{$month}/{$userId}";
        $rutaArchivo = $archivo->storeAs($directorio, $nombreSeguro, 'local');

        // Generar thumbnail solo para imágenes
        $rutaThumbnail = null;
        if ($esImagen) {
            $rutaThumbnail = $this->generarThumbnail($rutaArchivo, $directorio, $nombreSeguro);
        }

        // Crear el registro en base de datos
        $datos = [
            'user_id'        => $userId,
            'file_path'      => $rutaArchivo,
            'file_name'      => $archivo->getClientOriginalName(),
            'thumbnail_path' => $rutaThumbnail,
            'file_type'      => $esImagen ? 'image' : 'pdf',
            'file_size'      => $archivo->getSize(),
            'description'    => $descripcion,
            'uploaded_at'    => now(),
        ];

        // Asociar al modelo correcto
        if ($modelo instanceof Expense) {
            $datos['expense_id'] = $modelo->id;
        } elseif ($modelo instanceof Income) {
            $datos['income_id'] = $modelo->id;
        }

        return Receipt::create($datos);
    }

    /**
     * Detectar el MIME real del archivo usando finfo (más seguro que la extensión).
     */
    private function detectarMime(UploadedFile $archivo): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($archivo->getRealPath()) ?: $archivo->getMimeType();
    }

    /**
     * Generar thumbnail de 300px de ancho para imágenes.
     */
    private function generarThumbnail(string $rutaOriginal, string $directorio, string $nombreArchivo): string
    {
        $nombreThumb = 'thumb_' . $nombreArchivo;
        $rutaThumb   = $directorio . '/' . $nombreThumb;

        $contenido = Storage::disk('local')->get($rutaOriginal);

        $thumbnail = Image::read($contenido)
            ->scale(width: 300)
            ->toJpeg(quality: 80);

        Storage::disk('local')->put($rutaThumb, $thumbnail);

        return $rutaThumb;
    }

    /**
     * Eliminar archivo físico y su thumbnail del storage.
     */
    public function eliminar(Receipt $recibo): void
    {
        if ($recibo->file_path && Storage::disk('local')->exists($recibo->file_path)) {
            Storage::disk('local')->delete($recibo->file_path);
        }

        if ($recibo->thumbnail_path && Storage::disk('local')->exists($recibo->thumbnail_path)) {
            Storage::disk('local')->delete($recibo->thumbnail_path);
        }
    }
}
