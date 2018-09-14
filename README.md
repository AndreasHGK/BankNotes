# BankNotes

## General info
[![](https://poggit.pmmp.io/shield.state/BankNotes)](https://poggit.pmmp.io/p/BankNotes)
[![](https://poggit.pmmp.io/shield.api/BankNotes)](https://poggit.pmmp.io/p/BankNotes)


Turn your EconomyAPI money into items to trade them!
**Right-Click** to deposit a note and type **/note {amount}** to get a note.
You can change the lore/name/ID of the banknotes in v1.1.0 and up and you can change all the messages in V1.2.0 and up.

You will need EconomyAPI for this to work!

This is just a simple, but fun plugin.

All notes from v1.0.0 won't be recognized in v1.1.0 or later!

## Setup
This plugin doesn't require a setup, as it works when you install it, but there now is a config where you can change some visual stuff if you want to.

The first section contains settings for the notes. You can change the ID, custom name and custom lore for the note item.

The second section allows you to change most of the messages of the plugin. This is usefull if you want to translate the plugin ur even just change the looks of the messages.

The third section contains advanced settings, currently allowing you to reset economy by changing the economyid.
Also, enabled by default, the compatibility setting makes sure banknotes from v1.1.0 still work.

## For developers
Recently, i've added some API related stuff to make life easier should you want to make a plugin that works with banknotes.

Firstly, make sure you added `use AndreasHGK\BankNotes\Main;` to be able to acces BankNotes.

Here are some functions you can call:
```php
$bn = BankNotes::getInstance();

/**
* @return int $version
*/
$bn->getVersion();

/**
* @return int $noteVersion
*/
$bn->getNoteVersion();

/**
* @return int[] $compatibleVersions
*/
$bn->getCompatibleVersions();

/**
* @param item $note
*
* @return bool $isValid
*/
$bn->checkValidity($note);

/**
* @param int $value
*
* @return item $noteItem
*/
$bn->noteItem($value);
```

To see all functions, i recommend checking out BankNotes.php