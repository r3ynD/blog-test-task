<?php

declare(strict_types=1);

namespace App\Dto;

use App\Exception\ValidationException;
use DateTimeImmutable;

class ArticleFilter
{
    private function __construct(
        public readonly string $search,
        public readonly string $author,
        public readonly ?int $categoryId,
        public readonly string $dateFrom,
        public readonly string $dateTo,
        public readonly string $sort
    ) {
    }

    public static function fromQuery(array $query): self
    {
        $values = [];
        foreach (['q', 'author', 'category', 'from', 'to', 'sort'] as $field) {
            $value = $query[$field] ?? '';
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
                throw new ValidationException('Проверьте параметры фильтра.');
            }
            $values[$field] = trim($value);
        }
        if (mb_strlen($values['q']) > 150) {
            throw new ValidationException('Поисковый запрос должен быть не длиннее 150 символов.');
        }
        if ($values['author'] !== '' && !preg_match('/\A[a-zA-Z0-9_]{1,32}\z/', $values['author'])) {
            throw new ValidationException('Ник автора: до 32 латинских букв, цифр или знаков _.');
        }
        $categoryId = null;
        if ($values['category'] !== '') {
            $categoryId = filter_var($values['category'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 4294967295],
            ]);
            if ($categoryId === false) {
                throw new ValidationException('Выберите существующую категорию.');
            }
        }
        foreach (['from', 'to'] as $field) {
            if ($values[$field] === '') {
                continue;
            }
            if (!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $values[$field])) {
                throw new ValidationException('Укажите даты в формате ГГГГ-ММ-ДД.');
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values[$field]);
            if (!$date || $date->format('Y-m-d') !== $values[$field] || $values[$field] < '1000-01-01'
                || $values[$field] > '9999-12-31') {
                throw new ValidationException('Укажите даты в формате ГГГГ-ММ-ДД.');
            }
        }
        if ($values['from'] !== '' && $values['to'] !== '' && $values['from'] > $values['to']) {
            throw new ValidationException('Начало периода не может быть позже окончания.');
        }
        return new self(
            $values['q'], strtolower($values['author']), $categoryId, $values['from'], $values['to'],
            in_array($values['sort'], ['date', 'views'], true) ? $values['sort'] : 'date'
        );
    }

    public function queryParameters(): array
    {
        return array_filter([
            'q' => $this->search, 'author' => $this->author, 'category' => $this->categoryId,
            'from' => $this->dateFrom, 'to' => $this->dateTo, 'sort' => $this->sort,
        ], static fn ($value) => $value !== '' && $value !== null);
    }
}
