<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 404 dengan pesan khusus untuk pengguna (Bahasa Indonesia).
 * NotFoundHttpException biasa tetap dibalas "Data tidak ditemukan." agar pesan internal
 * (mis. dari route model binding) tidak bocor ke client.
 */
class DataTidakDitemukanException extends NotFoundHttpException {}
