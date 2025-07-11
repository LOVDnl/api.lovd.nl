# api.lovd.nl
This repository hosts the code for the generic LOVD APIs
 that are available at api.LOVD.nl.
The code for these APIs is available under the terms of the GPLv3 license.





<!-- Based on the LOVD3 manual -->
## Terms of service and fair use policy
If you wish to use the APIs directly through `api.lovd.nl`, then please follow
 the following terms of service and fair use policy.

- Read the API possibilities below carefully, and choose the most efficient
   method to query the API &mdash; meaning, the least amount of requests to get
   the information you need.
- Limit your requests to a maximum of 5 per second per server/domain name.
- Not required, but appreciated; please set a `User-agent` string that
   identifies you, so we can see how the service is being used and we can
   contact you if necessary.





## Installation
If you wish to run a copy of these APIs locally, all you need is to place the
 files in the `src` directory in a location that is available to a PHP-enabled
 web server such as Apache.





<!-- Based on the LOVD3 manual -->
## Different versions of this API
**Before you use the API, make sure you read this information, to prevent
 unexpected problems if we would update the API.**

To allow easy further development of this API,
 we may change the way the API works or change the way the API returns data.
To make sure this doesn't harm your application,
 you can instruct the API to use a fixed version.
To do so, you must include the API's version in the URL.
For instance, to always use
 [version 1](https://api.lovd.nl/v1/checkHGVS/NM_002225.3%3Ac.157C%3ET)
 of the API, even with
 [version 2](https://api.lovd.nl/v2/checkHGVS/NM_002225.3%3Ac.157C%3ET)
 now released, use `/v1/checkHGVS` instead of `/checkHGVS`.
_When you're not supplying a version number in the URL,
 the API will automatically use the latest version._
_This may cause your calls to fail if we release another update._
Make a decision based on what works best for you in your situation.



### Version 1 manual
The manual specifically for version 1
 [can be found here](https://github.com/LOVDnl/api.lovd.nl/blob/be43d94dc8703cf5224ed6a9ab918738ea24ba91/README.md#api-endpoints).
Below is the updated manual for version 2.





## General information about the output
Besides the `version` key, all outputs also show the `messages`, `warnings`,
 `errors`, and `data` keys.
Note that the first `messages`, `warnings`, and `errors` arrays describe the
 request as a whole, while those possibly found within the `data` object are
 specific for the given query.
Errors are, in general, non-recoverable.
Warnings are, in general, recoverable and repairable.
Messages are simply for your information.
Due to limitations of our implementation of PHP's `json_encode()`, these objects
 will be arrays when empty.
This may be corrected in a later version of the API.
The `data` array contains the output of your query.
This is empty when the query did not produce any output or if there was a
 problem with processing your query.
Otherwise, `data` holds an array of query results; one result per query.

All `messages`, `warnings`, and `errors` within the result objects in the `data`
 array return a code, e.g., `WWRONGTYPE`, as well as a human-readable text.
Codes allow you to interpret the meaning of the feedback without the need to
 read it or rely on the stability of the verbose strings.
We stress that between different library versions, the strings may be updated.
Therefore, use the stable codes to recognize the type of feedback given.
The text is meant for human users, and can be used by you for this purpose.

The first letter of each code describes the type of reply;
 `I` for information (messages), `W` for warning, and `E` for error.
This allows you to group these objects if needed, while still being clear on the
 origin of each entry.
Note also that errors and warnings exist with similar codes, e.g., `EWRONGTYPE`
 and `WWRONGTYPE`.

API endpoints that use the
 [LOVD HGVS library](https://github.com/LOVDnl/HGVS-syntax-checker)
 also return a `versions` key in the main result object.
The `versions` object collects all relevant versions related to the library.
The `library_date` shows the date the internal library that interprets
 variant descriptions and provides feedback and possible corrections, was
 updated.
The `library_version` shows the current version of this library.
An update to this library will not create a new API version,
 as the API version defines the behaviour of the API and its output.
The `HGVS_nomenclature_versions` object shows supported HGVS nomenclature
 versions for input (minimum, maximum) and for output.
The `caches` object shows the date that the gene cache has been updated.





## API endpoints
### /hello
Use this method just to see if the API is alive or not.
If it is, it will return an HTTP 200 status with the following output.

```json
{
    "version": 2,
    "messages": [
        "Hello!"
    ],
    "warnings": [],
    "errors": [],
    "data": []
}
```

#### API possibilities
This API doesn't support any input.



### /checkGene (v2 only)
Validate one or more gene symbols or identifiers using this API.
It recognizes discontinued gene symbols and aliases.
The API will return the HGNC ID and the official gene symbol.

#### API possibilities
The JSON schema for the API output is encoded in the API itself and can be
 accessed by opening the URL `/checkGene/schema.json`.
If you want to retrieve the schema for a certain version,
 use `/v2/checkGene/schema.json`.
As an example, see https://api.lovd.nl/v2/checkGene/schema.json.

##### Single query
To submit a single query, e.g., `IVD`, simply add it to the URL.
If special characters are used, follow the requirements for URL encoding.

```
https://api.lovd.nl/v2/checkGene/IVD
```

```json
{
    "version": 2,
    "messages": [
        "Successfully received 1 query."
    ],
    "warnings": [],
    "errors": [],
    "data": [
        {
            "input": "IVD",
            "identified_as": "gene_symbol",
            "identified_as_formatted": "gene symbol",
            "valid": true,
            "messages": [],
            "warnings": [],
            "errors": [],
            "data": {
                "hgnc_id": 6186
            },
            "corrected_values": {
                "IVD": 1
            }
        }
    ],
    "versions": {
        "library_date": "2025-07-08",
        "library_version": "0.5.0",
        "HGVS_nomenclature_versions": {
            "input": {
                "minimum": "15.11",
                "maximum": "21.1.3"
            },
            "output": "21.1.3"
        },
        "caches": {
            "genes": "2025-07-08"
        }
    }
}
```

The `identified_as` and `identified_as_formatted` fields show whether your
 query was identified as a gene symbol or an HGNC ID.
The `valid` boolean shows whether your input was valid; deprecated gene symbols
 or aliases will return `false` here.
The `hgnc_id` field will list the HGNC ID for the given gene.
The `corrected_values` object will list the official gene symbol and the
 associated confidence score between near-zero and one, indicating how sure the
 library is that its suggestion represents the gene you meant to describe.

##### Multiple queries
To submit multiple queries in one request, present them as a
 JSON array, added to the URL using the standard URL encoding.
For instance, to submit `BRCA1` and `HGNC:1101` for validation,
 you should construct an JSON array like so:

```json
["BRCA1","HGNC:1101"]
```

which is then URL encoded to:

```
%5B%22BRCA1%22%2C%22HGNC%3A1101%22%5D
```

We decided on this structure for compatibility with the `checkHGVS` endpoint.
Lots of possible single character separators are now,
 or maybe in the future, used as a part of the HGVS nomenclature.
E.g., the forward slash is used to indicate mosaicism and chimerism, and the
 pipe is used for non-sequence related changes such as loss of methylation.

```
http://localhost/git/api.lovd.nl/src/v2/checkGene/%5B%22BRCA1%22%2C%22HGNC%3A1101%22%5D
```

```json
{
    "version": 2,
    "messages": [
        "Successfully received 2 queries."
    ],
    "warnings": [],
    "errors": [],
    "data": [
        {
            "input": "BRCA1",
            "identified_as": "gene_symbol",
            "identified_as_formatted": "gene symbol",
            "valid": true,
            "messages": [],
            "warnings": [],
            "errors": [],
            "data": {
                "hgnc_id": 1100
            },
            "corrected_values": {
                "BRCA1": 1
            }
        },
        {
            "input": "HGNC:1101",
            "identified_as": "HGNC_ID",
            "identified_as_formatted": "HGNC ID",
            "valid": true,
            "messages": {
                "ISYMBOLFOUND": "The HGNC ID 1101 points to gene symbol \"BRCA2\"."
            },
            "warnings": [],
            "errors": [],
            "data": {
                "hgnc_id": "1101"
            },
            "corrected_values": {
                "BRCA2": 1
            }
        }
    ],
    "versions": {
        "library_date": "2025-07-08",
        "library_version": "0.5.0",
        "HGVS_nomenclature_versions": {
            "input": {
                "minimum": "15.11",
                "maximum": "21.1.3"
            },
            "output": "21.1.3"
        },
        "caches": {
            "genes": "2025-07-08"
        }
    }
}
```



### /checkHGVS
Validate a single variant description or
 a set of variant descriptions using this API.
It will return informative messages, warnings, and/or errors about the variant
 description and may suggest improvements in case an issue has been identified.

#### API possibilities
The JSON schema for the API output is encoded in the API itself and can be
 accessed by opening the URL `/checkHGVS/schema.json`.
If you want to retrieve the schema for a certain version,
 use `/v2/checkHGVS/schema.json`.
As an example, see https://api.lovd.nl/v2/checkHGVS/schema.json.

##### Single variant input
To submit a single variant description, e.g., `NM_002225.3:c.157C>T`, simply add
 it to the URL following the requirements for URL encoding:

```
https://api.lovd.nl/v2/checkHGVS/NM_002225.3%3Ac.157C%3ET
```

```json
{
    "version": 2,
    "messages": [
        "Successfully received 1 variant description.",
        "Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.",
        "For sequence-level validation of DNA variants, please use https://variantvalidator.org."
    ],
    "warnings": [],
    "errors": [],
    "data": [
        {
            "input": "NM_002225.3:c.157C>T",
            "identified_as": "full_variant_DNA",
            "identified_as_formatted": "full variant (DNA)",
            "valid": true,
            "messages": [],
            "warnings": [],
            "errors": [],
            "data": {
                "position_start": 157,
                "position_end": 157,
                "position_start_intron": 0,
                "position_end_intron": 0,
                "range": false,
                "type": ">"
            },
            "corrected_values": {
                "NM_002225.3:c.157C>T": 1
            }
        }
    ],
    "versions": {
        "library_date": "2025-07-08",
        "library_version": "0.5.0",
        "HGVS_nomenclature_versions": {
            "input": {
                "minimum": "15.11",
                "maximum": "21.1.3"
            },
            "output": "21.1.3"
        },
        "caches": {
            "genes": "2025-07-08"
        }
    }
}
```

Problems are automatically fixed if possible:

```
https://api.lovd.nl/v2/checkHGVS/NM_002225.3%3Ac.157delCinsT
```

```json
{
    "version": 2,
    "messages": [
        "Successfully received 1 variant description.",
        "Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.",
        "For sequence-level validation of DNA variants, please use https://variantvalidator.org."
    ],
    "warnings": [],
    "errors": [],
    "data": [
        {
            "input": "NM_002225.3:c.157delCinsT",
            "identified_as": "full_variant_DNA",
            "identified_as_formatted": "full variant (DNA)",
            "valid": false,
            "messages": [],
            "warnings": {
                "WWRONGTYPE": "Based on the given sequences, this deletion-insertion should be described as a substitution."
            },
            "errors": [],
            "data": {
                "position_start": 157,
                "position_end": 157,
                "position_start_intron": 0,
                "position_end_intron": 0,
                "range": false,
                "type": "delins"
            },
            "corrected_values": {
                "NM_002225.3:c.157C>T": 1
            }
        }
    ],
    "versions": {
        "library_date": "2025-07-08",
        "library_version": "0.5.0",
        "HGVS_nomenclature_versions": {
            "input": {
                "minimum": "15.11",
                "maximum": "21.1.3"
            },
            "output": "21.1.3"
        },
        "caches": {
            "genes": "2025-07-08"
        }
    }
}
```

Information about `messages`, `warnings`, and `errors` within the `data` object
 is explained under
 "[General information about the output](#general-information-about-the-output)".

When requesting a variant that contains incorrect syntax, the API will attempt
 to repair your description.
If this results in one or more suggested corrections,
 these suggestions are always provided with a confidence score between
 near-zero and one, indicating how sure the library is that its suggestion
 represents the variant you meant to describe.

##### Multiple variant input
To submit multiple variant descriptions in one request, present them as a
 JSON array, added to the URL using the standard URL encoding.
For instance, to submit `c.157C>T` and `g.40699840C>T` for validation,
 you should construct an JSON array like so:

```json
["c.157C>T","g.40699840C>T"]
```

which is then URL encoded to:

```
%5B%22c.157C%3ET%22%2C%22g.40699840C%3ET%22%5D
```

We decided on this structure since lots of possible single character separators
 are now, or maybe in the future, used as a part of the HGVS nomenclature.
E.g., the forward slash is used to indicate mosaicism and chimerism, and the
 pipe is used for non-sequence related changes such as loss of methylation.

```
https://api.lovd.nl/v2/checkHGVS/%5B%22c.157C%3ET%22%2C%22g.40699840C%3ET%22%5D
```

```json
{
    "version": 2,
    "messages": [
        "Successfully received 2 variant descriptions.",
        "Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.",
        "For sequence-level validation of DNA variants, please use https://variantvalidator.org."
    ],
    "warnings": [],
    "errors": [],
    "data": [
        {
            "input": "c.157C>T",
            "identified_as": "variant_DNA",
            "identified_as_formatted": "variant (DNA)",
            "valid": true,
            "messages": {
                "IREFSEQMISSING": "Please note that your variant description is missing a reference sequence. Although this is not necessary for our syntax check, a variant description does need a reference sequence to be fully informative and HGVS-compliant."
            },
            "warnings": [],
            "errors": [],
            "data": {
                "position_start": 157,
                "position_end": 157,
                "position_start_intron": 0,
                "position_end_intron": 0,
                "range": false,
                "type": ">"
            },
            "corrected_values": {
                "c.157C>T": 1
            }
        },
        {
            "input": "g.40699840C>T",
            "identified_as": "variant_DNA",
            "identified_as_formatted": "variant (DNA)",
            "valid": true,
            "messages": {
                "IREFSEQMISSING": "Please note that your variant description is missing a reference sequence. Although this is not necessary for our syntax check, a variant description does need a reference sequence to be fully informative and HGVS-compliant."
            },
            "warnings": [],
            "errors": [],
            "data": {
                "position_start": 40699840,
                "position_end": 40699840,
                "range": false,
                "type": ">"
            },
            "corrected_values": {
                "g.40699840C>T": 1
            }
        }
    ],
    "versions": {
        "library_date": "2025-07-08",
        "library_version": "0.5.0",
        "HGVS_nomenclature_versions": {
            "input": {
                "minimum": "15.11",
                "maximum": "21.1.3"
            },
            "output": "21.1.3"
        },
        "caches": {
            "genes": "2025-07-08"
        }
    }
}
```

More information on the output can be found under "[Single variant input](#single-variant-input)".
