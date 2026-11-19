@props(['paginator', 'elements' => null])

<x-pagination :paginator="$paginator" :elements="$elements ?? null" />
