<?php

namespace App\Support;

use App\Models\Area;
use App\Models\College;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Parameter;
use App\Models\SurveyVisit;

/** URLs inside a binder: staff routes, or the accreditor's token-scoped review routes. */
class BinderLinks
{
    public function __construct(public ?string $token = null)
    {
    }

    public function visit(College $college, SurveyVisit $visit): string
    {
        return $this->token ? route('review.visit', $this->token) : route('binder.visit', [$college, $visit]);
    }

    public function node(College $college, Area $area, ?Parameter $parameter = null): string
    {
        return $this->token
            ? route('review.node', array_filter([$this->token, $area->id, $parameter?->id]))
            : route('binder.node', array_filter([$college->id, $area->id, $parameter?->id]));
    }

    public function folder(Folder $folder): string
    {
        return $this->token ? route('review.folder', [$this->token, $folder]) : route('binder.folder', $folder);
    }

    public function document(Document $document, bool $inline = false): string
    {
        $url = $this->token ? route('review.document', [$this->token, $document]) : route('documents.show', $document);

        return $inline ? $url . '?inline=1' : $url;
    }
}
