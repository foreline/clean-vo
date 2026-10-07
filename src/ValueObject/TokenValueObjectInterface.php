<?php
declare(strict_types=1);

namespace Domain\ValueObject;

/**
 * Маркерный интерфейс объекта-значения токена/пароля.
 *
 * Используется генератором кода (foreline/crud) для определения типа поля:
 * свойства с VO, реализующим этот интерфейс, получают ui.type = password
 * (маскированный ввод, исключение из гридов).
 */
interface TokenValueObjectInterface extends StringValueObjectInterface
{
}
