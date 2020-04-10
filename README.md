[![Build Status](https://travis-ci.org/philipp-riddle/SymfonyGoogleSheetsTranslationBundle.svg?branch=master)](https://travis-ci.org/philipp-riddle/SymfonyGoogleSheetsTranslationBundle)

# SymfonyGoogleSheetsTranslationBundle

## Introduction
Translation management can be rather... pesky. This bundle allows you to use a single Googlesheet and push your translations to your symfony site within seconds.

**Features:**
- *Use as many languages as you want:* this bundle sets you no limits, from just one locale (use case: you just want to keep texts out of your lovely source code) to even thirteen locales (because you just learned them on Dualingo)
- *Decide which sheet page you want to use for your translations:* Only the first sheet page of your GoogleSheet should be used for translations? No problem, you need one more setting. Although this is possible, we recommend to use the default mode which automatically includes all the sheets and merges them into one (translations-wise - **this bundle does not edit your GoogleSheet**).
- *One command to push it all...:* This bundle also provides you with one command which enables you to reload & push your translations in all available languages within seconds

**Requirements**
- Symfony 5.0 (soon, work in progress: check out the "4.4" branch to use this bundle with Symfony 4.4)
- PHP >= 7.2.5

## Usage
The first thing you need is a Googlesheet, go ahead and create on [sheets.google.com](https://sheets.google.com). My demo translations sheet is available at [this link](https://docs.google.com/spreadsheets/d/1D2qOEgEKgMy7qh0B-PQMzdil8AoE5NvYMsNuusqM-IA/edit?usp=sharing). I'd suggest that you just duplicate that one into your account / apply this structure - feel free to change the head => add as many locales as you want!
> Better customization & configuration possibilities for the GoogleSheet are in the works - for now, just try to stick as close as possible to the structure.

### Publishing your sheet... to the web?
Now, you need to publish it to the web. What first sounded very weird to me (should I really publish my translations publicly to the web?!), isn't really that dangerous. The chances of someone finding your translations is really low and on top of that no one can use it to attack you / do harm to your site.

**Step-by-step publishing:**
1. Go to File > Publish to the web: ![Publish Step 1](https://i.imgur.com/kpJj7nb.png)
2. A window should pop up, just press 'Publish' (assuming that you want to publish the whole sheet): ![Publish Step 2](https://i.imgur.com/TrIhthz.png)
3. Now, you need to extract the ID of your sheet. Back to the URL window (CMD + L / CTRL + L for hot key lovers), copy the part  which is marked in the following image: ![Publish Step 3](https://i.imgur.com/yG0kXEi.png).
4. Finally we can hop back in Symfony. In **config/services.yaml** insert it like the following:
```yaml
parameters:
    ...
    googlesheets_translations.sheet.publicId: '1D2qOEgEKgMy7qh0B-PQMzdil8AoE5NvYMsNuusqM-IA'
    ...
```
I went ahead and already pasted in the ID from above.

5. (Optional) Configure which sheet pages you want to add. If you want to only push a single sheet page, you have to add the following parameter:
```yaml
parameters:
    ...
    googlesheets_translations.sheet.mode: 'all' # that's the default value
    # or...
    googlesheets_translations.sheet.mode: 1     # if you only want to use the first sheet page for your translations
    ...
```
My final config (config/services.yaml):
```yaml
parameters:
    googlesheets_translations.sheet.mode: 'all'
    googlesheets_translations.sheet.publicId: '1E9r6LCSeQV6rrEVq2YuSnm1OEbbTjgc50G9OHHUvihk'
```

6. Add the bundles' translation loader to your Symfony application:

7. Push your translations with the built-in command: `phiil:translation:reload` (in my case, I have to execute `php bin/console phiil:translation:reload`).

#### Congratulations, you're all set!

## Problems? Issues?
Just post them here on Github or contact me via E-Mail: [philipp@riddle.com](mailto:philipp@riddle.com). Feel free to contribute!
