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
