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
 we might change the way the API works or change the way the API returns data.
To make sure this doesn't harm your application,
 you can instruct the API to use a fixed version.
To do so, you must include the APIs version in the URL.
For instance, to always use version 1 of the API,
 even when version 2 is already released, use
 `/v1/checkHGVS` instead of `/checkHGVS`.
_When you're not supplying a version number in the URL,
 the API will automatically use the latest version._
Make a decision based on what works best for you in your situation.





## API endpoints
### /hello
Use this method just to see if the API is alive or not.
If it is, it will return an HTTP 200 status with the following output.
```json
{
  "version": 1,
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



### /checkHGVS
Validate a single variant description or
 a set of variant descriptions using this API.
It will return informative messages, warnings, and/or errors about the variant
 description and may suggest improvements in case an issue has been identified.

#### API possibilities
The JSON schema for the API output is encoded in the API itself and can be
 accessed by opening the URL `/checkHGVS/schema.json`.
If you want to retrieve the schema for a certain version,
 use `/v1/checkHGVS/schema.json`.
As an example, see https://api.lovd.nl/v1/checkHGVS/schema.json.

##### Single variant input
To submit a single variant description, e.g., `NM_002225.3:c.157C>T`, simply add
 it to the URL following the requirements for URL encoding:
```
https://api.lovd.nl/v1/checkHGVS/NM_002225.3%3Ac.157C%3ET
```
```json
{
    "version": 1,
    "messages": [
        "Successfully received 1 variant description.",
        "Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.",
        "For sequence-level validation of DNA variants, please use https:\/\/variantvalidator.org."
    ],
    "warnings": [],
    "errors": [],
    "data": {
        "NM_002225.3:c.157C>T": {
            "messages": {
                "IOK": "This variant description is HGVS-compliant."
            },
            "warnings": [],
            "errors": [],
            "data": {
                "position_start": 157,
                "position_end": 157,
                "type": "subst",
                "range": false,
                "suggested_correction": []
            }
        }
    },
    "library_version": "2022-09-02"
}
```

Note that the first `messages`, `warnings`, and `errors` arrays describe the
 request as a whole, while those whithin the `data` object are specific for the
 given variant.
Errors are, in general, non-recoverable.
Warnings are, in general, recoverable and easily repairable.
Messages are simply for your information.
Due to limitations of our implementation of PHP's `json_encode()`, these objects
 will be arrays when empty.
This applies as well to the `suggested_correction` object.
This may be corrected in a later version of the API.

The `library_version` shows the date the internal libraries that interpret
 variant descriptions and provide feedback and possible corrections, were
 updated.
Such an update will not create a new API version, as the API version defines
 the behaviour of the API and its output.

```
https://api.lovd.nl/v1/checkHGVS/NM_002225.3%3Ac.157delCinsT
```
```json
{
    "version": 1,
    "messages": [
        "Successfully received 1 variant description.",
        "Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.",
        "For sequence-level validation of DNA variants, please use https:\/\/variantvalidator.org."
    ],
    "warnings": [],
    "errors": [],
    "data": {
        "NM_002225.3:c.157delCinsT": {
            "messages": [],
            "warnings": {
                "WWRONGTYPE": "A deletion-insertion of one base to one base should be described as a substitution. Please rewrite \"delCinsT\" to \"C>T\"."
            },
            "errors": [],
            "data": {
                "position_start": 157,
                "position_end": 157,
                "type": "delins",
                "range": false,
                "suggested_correction": {
                    "value": "NM_002225.3:c.157C>T",
                    "confidence": "high"
                }
            }
        }
    },
    "library_version": "2022-09-02"
}
```

All `messages`, `warnings`, and `errors` within the `data` object return a code,
 e.g., `WWRONGTYPE`, as well as a human-readable text.
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

When requesting a variant that contains incorrect syntax, the API will attempt
 to repair your description.
If this results in a suggested correction, this suggestion is always provided
 with a confidence of either `high`, `medium`, or `low`, indicating how sure the
 library is that its suggestion describes the variant you meant to describe.

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
https://api.lovd.nl/v1/checkHGVS/%5B%22c.157C%3ET%22%2C%22g.40699840C%3ET%22%5D
```
```json
{
    "version": 1,
    "messages": [
        "Successfully received 2 variant descriptions.",
        "Note that this API does not validate variants on the sequence level, but only checks if the variant description follows the HGVS nomenclature rules.",
        "For sequence-level validation of DNA variants, please use https:\/\/variantvalidator.org."
    ],
    "warnings": [],
    "errors": [],
    "data": {
        "c.157C>T": {
            "messages": {
                "IOK": "This variant description is HGVS-compliant.",
                "IREFSEQMISSING": "Please note that your variant description is missing a reference sequence. Although this is not necessary for our syntax check, a variant description does need a reference sequence to be fully informative and HGVS-compliant."
            },
            "warnings": [],
            "errors": [],
            "data": {
                "position_start": 157,
                "position_end": 157,
                "type": "subst",
                "range": false,
                "suggested_correction": []
            }
        },
        "g.40699840C>T": {
            "messages": {
                "IOK": "This variant description is HGVS-compliant.",
                "IREFSEQMISSING": "Please note that your variant description is missing a reference sequence. Although this is not necessary for our syntax check, a variant description does need a reference sequence to be fully informative and HGVS-compliant."
            },
            "warnings": [],
            "errors": [],
            "data": {
                "position_start": 40699840,
                "position_end": 40699840,
                "type": "subst",
                "range": false,
                "suggested_correction": []
            }
        }
    },
    "library_version": "2022-09-02"
}
```
If the same variant description has been submitted more than once in the same
 request, it will be presented in the output only once since the descriptions
 are used as keys.

More information on the output can be found under "[Single variant input](#single-variant-input)".
