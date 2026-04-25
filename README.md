# foreline/clean-vo

Domain Value Objects for the [`foreline/clean`](https://github.com/foreline/clean)
clean-architecture framework.

This library packages a collection of battle-tested Value Objects that follow the
`foreline/clean` VO conventions and interfaces (`ValueObjectInterface`,
`StringValueObjectInterface`, `IntValueObjectInterface`,
`MixedValueObjectInterface`, `CollectionInterface`).

It also ships ready-to-use [`foreline/crud`](https://github.com/foreline/crud)
UI configuration so that consuming applications get sensible defaults
out-of-the-box.

## Installation

```bash
composer require foreline/clean-vo
```

## Provided Value Objects

| Namespace                                       | Class(es)                                                                                                                 |
| ----------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| `Domain\ValueObject\Money`                      | `Money`, `Currency`, `MoneyBag`, `Currency\BYN`, `Currency\CNY`, `Currency\EUR`, `Currency\RUB`, `Currency\RUR`, `Currency\USD` |
| `Domain\ValueObject\IPv4`                       | `IPv4`, `IPv4Mask`                                                                                                        |
| `Domain\ValueObject\NetworkIdentifier`          | `Domain`, `DomainCollection`, `Email`, `EmailCollection`, `MacAddress`, `MacAddressCollection`, `Port`, `QueryParameter`, `QueryParameters`, `Scheme`, `TopLevelDomain`, `URI`, `URICollection` |
| `Domain\ValueObject`                            | `FilePath`                                                                                                                |

## foreline/crud integration

The `extra.foreline-crud.vo-config-paths` entry in `composer.json` points
`foreline/crud` at `config/crud/vo/`, where each VO ships its UI defaults
(input type, CSS classes, HTML attributes, placeholder, …).

Currently shipped configs:

- `config/crud/vo/Domain/ValueObject/Money/Money.json`
- `config/crud/vo/Domain/ValueObject/IPv4/IPv4.json`

Project-level overrides under `config/crud/vo/` always win. See
[`foreline/crud` docs](https://github.com/foreline/crud/blob/main/docs/vo/value_object_config.md)
for the full schema.

## Testing

```bash
composer install
composer test
```

## License

MIT
