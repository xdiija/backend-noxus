# Glossary

Domain terminology. Table/column names are English; user-facing values (statuses,
categories) are Portuguese. This maps the two.

| English (schema) | Portuguese (domain) | Meaning |
|------------------|---------------------|---------|
| organization | organização / ONG | A shelter or NGO tenant. Top of the multi-tenant hierarchy. |
| animal | animal | A rescued dog/cat (or other species) under an organization's care. |
| species | espécie | Cachorro, Gato, … |
| breed | raça | Breed within a species; `SRD (Vira-lata)` = mixed breed. |
| size | porte | Pequeno / Médio / Grande / Gigante. |
| neutered | castrado | Spayed/neutered flag + date. Key adoption/health attribute. |
| microchip | microchip | Unique implanted ID. |
| rescue | resgate | The single intake event that brought the animal into care. |
| adopter | adotante | Person who adopts an animal. |
| adoption | adoção | The act/record of placing an animal with an adopter. |
| return | devolução | An adopted animal brought back; a new adoption cycle may follow. |
| follow-up | acompanhamento | Post-adoption check-in on the animal's welfare. |
| foster home | lar temporário | Temporary volunteer home; animal stays but isn't adopted. |
| volunteer | voluntário | Person who helps the organization (not necessarily a system user). |
| donor | doador | Person/entity making donations. |
| donation | doação | Money or in-kind contribution. |
| expense | despesa | Organization outgoing cost. |
| health record | prontuário / registro de saúde | Generic clinical event/note. |
| vaccine | vacina | Vaccine catalog entry. |
| appointment | consulta | Scheduled vet visit. |
| surgery | cirurgia | Surgical procedure (incl. castration). |
| tax_id | CNPJ | Organization's Brazilian company registration number. |
| cpf | CPF | Brazilian individual taxpayer ID (adopters). |
| status (lookup) | situação / status | Configurable state (animal, adoption, follow-up). |
