# DokuWiki Infobox Plugin

A flexible infobox plugin for DokuWiki that creates structured information boxes for any type of content.

![Example Image](https://github.com/user-attachments/assets/7fc33a7c-541c-4a40-938b-7c3e462630c3)

Features
--------
* Multiple images with tab navigation
* Section headers with optional collapsible sections
* Spoiler/blur functionality for sensitive content
* Divider lines for visual organization
* Automatically matches your DokuWiki theme
* Click images to view fullscreen
* Full wiki syntax support
* Keyboard navigation support

Installation
------------
1. Download the plugin
2. Create a new folder named `infobox` in your DokuWiki's `lib/plugins/` directory
3. Extract the downloaded files into this new folder
4. Log into your DokuWiki as an admin and go to the Configuration Settings page
5. Look for the infobox plugin in the plugin section and enable it if necessary

Alternatively:

1. Navigate to your DokuWiki Extension Manager page (Example: http://localhost:8800/doku.php?id=start&do=admin&page=extension&tab=search&q=Infobox)
2. Search for `Infobox` in the Search and Install tab and look for this plugin
3. Click Install

Basic Usage
-----------
```markdown
{{infobox>
name = Name
image = example.jpg
Field 1 = Text 1
Field 2 = Text 2
}}
```

Header Image
------------
Add a full-width banner image at the top of the infobox that spans the entire width:
```markdown
{{infobox>
header_image = banner.jpg
name = Character Name
image = portrait.jpg
Field 1 = Text 1
Field 2 = Text 2
}}
```

Multiple Images
---------------
```markdown
{{infobox>
name = Name
image1 = example1.jpg|Image Tab 1
image2 = example2.jpg|Image Tab 2
image3 = example3.jpg|Image Tab 3
Field 1 = Text 1
Field 2 = Text 2
}}
```

With Sections
-------------
```markdown
{{infobox>
name = Name
image1 = example1.jpg|Image Tab 1
image2 = example2.jpg|Image Tab 2
image3 = example3.jpg|Image Tab 3

== Section 1 ==
Field 1 = Text 1
Field 2 = Text 2

== Section 2 ==
Field 1 = Text 1
Field 2 = Text 2

=== Collapsible Section ===
Field 1 = Text 1
Field 2 = Text 2
}}
```

With Struct Plugin
------------------
```markdown
{{infobox>
name = {{$project.name}}
status = {{$project.status}}
budget = {{$project.budget}}
deadline = {{$project.deadline}}
}}
```

With Subgroups within Sections
------------------
Use ::: to create side-by-side columns within any section:
```markdown
{{infobox>
name = Name
image1 = example1.png|Image Tab 1
image2 = example2.jpg|Image Tab 2
image3 = example3.jpg|Image Tab 3
Field 1 = Text 1
Field 2 = Text 2

== Timeline ==
::: ← Previous :::
Info 1 = [[Info 1]]
Info 2 = [[Info 1]]

::: → Next :::
Info 1 = [[Info 1]]
Info 2 = [[Info 1]]
Info 3 = [[Info 1]]
}}
```

Headerless Sections and Columns
------------------
Use `====` for sections without headers and `::::::` for columns without headers:
```markdown
{{infobox>
name = Name
image = example.jpg

====
::::::
Info 1 = [[Info 1]]
Info 2 = [[Info 2]]

::::::
Info 3 = [[Info 3]]
Info 4 = [[Info 4]]
Info 5 = [[Info 5]]
}}
```

Full-Width Values
------------------
Use `= value =` to create a row that spans both columns:
```markdown
{{infobox>
name = Character
image = photo.jpg
Class = Warrior
Level = 50

== Stats ==
= [[View Full Stats]] =
Health = 1000
Mana = 500
}}
```

Spoiler/Blur Content
--------------------
Use `!` prefix to blur sensitive content that can be revealed on hover or click:
```markdown
{{infobox>
name = Character Name
!secret_identity = !Bruce Wayne
real_name = !Clark Kent
image = character.jpg

== Personal Info ==
age = 30
!trauma = !Parents were murdered
occupation = Journalist
}}
```

**Spoiler Options:**
* `!key = value` - Blurs both the field name and value
* `key = !value` - Blurs only the field value
* **Interaction:** Hover to preview, click/Enter to permanently reveal
* **Accessibility:** Full keyboard navigation and screen reader support

Divider Lines
-------------
Add visual separators within your infobox using divider lines:
```markdown
{{infobox>
name = Character Name
image = character.jpg
real_name = Bruce Wayne
age = 30

divider = Background
origin = Gotham City
parents = Thomas and Martha Wayne

divider = Abilities
fighting = Expert
detective = Master
wealth = Billionaire

== Equipment ==
divider = Vehicles
car = Batmobile
plane = Batwing

divider = Gadgets
utility_belt = Various tools
grappling_gun = For mobility
}}
```


Syntax Reference
----------------
* Single image: `image = filename.jpg`
* Multiple images: `image1 = file.jpg|Label`
* Header banner: `header_image = banner.jpg`
* Section: `== Section Name ==`
* Collapsed section: `=== Section Name ===`
* Headerless section: `====`
* Subgroups: `::: Group Name :::`
* Headerless subgroup: `::::::`
* Full-width value: `= value =`
* Divider lines: `divider = Text`
* Spoiler content: `!key = value` or `key = !value`
* Custom CSS: `class = custom-style`
* Icons in field names: `icon.png|Field Name = Value`
* Lists in values: Use wiki list syntax

Image Tab Customization
----------------
Provide custom captions to make tabs clearer:
```markdown
image1 = photo.jpg|Main Photo
image2 = diagram.jpg|Technical Diagram
```

Add custom CSS to your theme:
```markdown
/* Option 1: Bordered tabs */
.infobox-tab {
    background: rgba(128, 128, 128, 0.1) !important;
    border: 1px solid rgba(128, 128, 128, 0.3) !important;
}

/* Option 2: Pill-style tabs */
.infobox-tab {
    border-radius: 4px !important;
    margin: 2px !important;
    background: rgba(128, 128, 128, 0.1) !important;
}

/* Option 3: Traditional tab style */
.infobox-tab {
    border: 1px solid !important;
    border-bottom: none !important;
    background: rgba(128, 128, 128, 0.05) !important;
}
.infobox-tab.active {
    background: inherit !important;
    font-weight: bold !important;
}
```
