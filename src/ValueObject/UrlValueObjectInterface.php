<?php
declare(strict_types=1);

namespace Domain\ValueObject;

/**
 * Маркерный интерфейс объекта-значения URL-адреса.
 *
 * Используется генератором кода (foreline/crud) для определения типа поля:
 * свойства с VO, реализующим этот интерфейс, получают ui.type = url.
 */
interface UrlValueObjectInterface extends StringValueObjectInterface
{
}
