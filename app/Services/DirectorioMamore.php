<?php

namespace App\Services;

use App\Exceptions\MamoreException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Directorio de funcionarios servido únicamente por la API de Mamoré: búsqueda
 * por CI o nombre y ficha por cédula, ya normalizadas a la forma que consumen
 * las vistas (`id`, `ci`, `nombre`, `profesion`, `pinReloj`, `nacimiento`,
 * `edad`, `ver`).
 *
 * No consulta la base local `personas`: para las pantallas que lo usan, Mamoré
 * es la única fuente de datos personales.
 *
 * @phpstan-type FilaFuncionario array{id: mixed, ci: string, nombre: string, profesion: string, pinReloj: string, nacimiento: ?string, edad: ?int, ver: ?string}
 */
class DirectorioMamore
{
    /**
     * Lote que se trae cuando hay que filtrar por varias palabras: la API busca
     * por un solo término, así que el cruce nombre + apellido se hace acá.
     */
    private const LOTE_MULTIPALABRA = 100;

    public function __construct(private MamoreClient $mamore) {}

    /**
     * ¿Está configurada la API? Sin eso el directorio no tiene de dónde leer.
     */
    public function configurado(): bool
    {
        return $this->mamore->configurado();
    }

    /**
     * Funcionarios que coinciden con el término buscado (CI o nombre).
     *
     * Con una sola palabra se delega en el `search` de la API. Con varias se
     * trae un lote por la palabra más larga y se filtra por todas, porque la API
     * no cruza nombre + apellido.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws MamoreException
     */
    public function buscar(string $q, int $limite = 20): Collection
    {
        $q = trim($q);

        if ($q === '') {
            return collect();
        }

        $terminos = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($terminos) <= 1) {
            $respuesta = $this->mamore->people(1, $limite, $q);

            return collect($this->normalizar($respuesta['data'] ?? []));
        }

        $masLargo = (string) collect($terminos)->sortByDesc(fn (string $t): int => mb_strlen($t))->first();
        $respuesta = $this->mamore->people(1, self::LOTE_MULTIPALABRA, $masLargo);

        return collect($this->normalizar($respuesta['data'] ?? []))
            ->filter(fn (array $fila): bool => $this->coincide($fila, $terminos))
            ->take($limite)
            ->values();
    }

    /**
     * Ficha de un funcionario por su cédula. `null` si Mamoré no lo tiene.
     *
     * @return array<string, mixed>|null
     *
     * @throws MamoreException
     */
    public function porCi(string $ci): ?array
    {
        $ci = trim($ci);

        if ($ci === '') {
            return null;
        }

        $persona = $this->mamore->personByCi($ci);

        return $persona === null ? null : $this->normalizarPersona($persona);
    }

    /**
     * Normaliza un lote de filas crudas de la API.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    public function normalizar(array $data): array
    {
        return collect($data)
            ->map(fn (array $persona): array => $this->normalizarPersona($persona))
            ->all();
    }

    /**
     * Normaliza una fila cruda de la API a la forma común de las vistas.
     *
     * @param  array<string, mixed>  $persona
     * @return array<string, mixed>
     */
    public function normalizarPersona(array $persona): array
    {
        $ci = trim((string) ($persona['ci'] ?? ''));
        $nacimiento = filled($persona['birthday'] ?? null) ? Carbon::parse($persona['birthday']) : null;
        // La API embute el contrato firmado en cada persona (null si no tiene).
        $contrato = is_array($persona['contrato'] ?? null) ? $persona['contrato'] : null;

        return [
            'id' => $persona['id'] ?? null,
            'ci' => $ci,
            'nombre' => trim((string) ($persona['full_name'] ?? trim(
                ($persona['first_name'] ?? '').' '.($persona['middle_name'] ?? '').' '
                .($persona['paternal_surname'] ?? '').' '.($persona['maternal_surname'] ?? '')
            ))) ?: '—',
            // «Apellidos Nombres», como los imprime el reporte de marcaciones
            // desde el sistema de escritorio viejo.
            'nombreFormal' => trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                trim((string) ($persona['paternal_surname'] ?? '')),
                trim((string) ($persona['maternal_surname'] ?? '')),
                trim((string) ($persona['first_name'] ?? '')),
                trim((string) ($persona['middle_name'] ?? '')),
            ]))) ?? ''),
            'profesion' => trim((string) ($persona['profession'] ?? '')),
            // En Mamoré el PIN del reloj es la misma cédula.
            'pinReloj' => $ci,
            'nacimiento' => $nacimiento?->format('d/m/Y'),
            'edad' => $nacimiento?->age,
            'ver' => $ci !== '' ? route('funcionarios.mamore', ['ci' => $ci]) : null,
            // Del contrato firmado, cuando la persona tiene uno.
            'cargo' => $contrato === null ? null : trim((string) (
                $contrato['cargo_completo'] ?? $contrato['cargo'] ?? $contrato['denominacion'] ?? ''
            )),
            'direccion' => $contrato === null ? null : trim((string) (
                $contrato['direccion_administrativa']['sigla']
                ?? $contrato['direccion_administrativa']['nombre']
                ?? ''
            )),
            // `has_contract` lo informa la API; null si no vino en la respuesta.
            'conContrato' => isset($persona['has_contract']) ? (bool) $persona['has_contract'] : null,
        ];
    }

    /**
     * Texto de una fila para los combos de funcionario: «CI — NOMBRE · cargo
     * (SIGLA)». El cargo y la dirección salen del contrato firmado, así que en
     * quien no tiene contrato queda solo «CI — NOMBRE».
     *
     * @param  array<string, mixed>  $fila
     */
    public function etiqueta(array $fila): string
    {
        $texto = trim((string) ($fila['ci'] ?? '')).' — '.trim((string) ($fila['nombre'] ?? 'Sin nombre'));
        $cargo = trim((string) ($fila['cargo'] ?? ''));
        $direccion = trim((string) ($fila['direccion'] ?? ''));

        if ($cargo !== '') {
            $texto .= ' · '.$cargo;
        }

        if ($direccion !== '') {
            $texto .= ' ('.$direccion.')';
        }

        return $texto;
    }

    /**
     * ¿La fila contiene todas las palabras buscadas (en su nombre o su CI)?
     *
     * @param  array<string, mixed>  $fila
     * @param  list<string>  $terminos
     */
    private function coincide(array $fila, array $terminos): bool
    {
        $heno = mb_strtolower($fila['nombre'].' '.$fila['ci']);

        foreach ($terminos as $termino) {
            if (! str_contains($heno, mb_strtolower($termino))) {
                return false;
            }
        }

        return true;
    }
}
