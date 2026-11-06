<?php

namespace App\Content;

/**
 * Единственный источник наполнения сайта.
 * Этап 1 — StaticContentRepository (PHP-массивы), этап 3 — реализация на базе данных.
 * Пути картинок — без расширения, относительно public/img (см. <x-picture>).
 */
interface ContentRepository
{
    /** @return array{title:string, subtitle:string, note:string, image:string, image_mobile:?string, focal:array{x:int,y:int}, subject_side:string} */
    public function hero(): array;

    /** @return list<string> */
    public function principles(): array;

    /** @return array{name:string, lead:string, paragraphs:list<string>, city:string, photo:string} */
    public function about(): array;

    /** @return list<array{title:string, period:?string, text:string, image:string}> */
    public function experience(): array;

    public function trainingIntro(): string;

    /** @return list<array{n:string, title:string, tags:list<string>, description:string}> */
    public function formats(): array;

    /** @return list<array{title:string, items:list<array{title:string, text:string}>}> */
    public function topicGroups(): array;

    /** @return list<array{title:string, text:string}> */
    public function eventServices(): array;

    /** @return list<array{slug:string, direction:string, title:string, client:string, date:string, people:string, request:string, plan:string, result:string, review:string, photos:list<string>, demo:bool}> */
    public function cases(): array;

    public function findCase(string $slug): ?array;

    /** @return list<array{slug:string, title:string, date:string, client:string, description:string, photos:list<string>, demo:bool}> newest first */
    public function events(?int $limit = null): array;

    public function findEvent(string $slug): ?array;

    /** @return list<array{name:string, short:string, logo:?string, description:string, url:?string}> */
    public function partners(): array;

    /** @return list<array{q:string, a:string}> */
    public function faq(): array;

    /** @return list<array{image:string, caption:string}> */
    public function gallery(): array;

    /** @return list<array{image:string, title:string, description:string, year:string}> */
    public function certificates(): array;

    /** @return array{phone:string, phone_href:string, vk:string, telegram:string, max:string} */
    public function contacts(): array;

    public function kp(): array;
}
