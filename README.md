# DokuWiki Infobox Plugin

A flexible infobox plugin for DokuWiki that creates structured information boxes for any type of content.

![Example Image](https://github.com/user-attachments/assets/83fb9a05-6d49-4ac1-92c2-f2759365c13b)

Features
--------
* Multiple images with tab navigation
* Section headers with optional collapsible sections
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

}}
```


Syntax Reference
----------------
* Single image: `image = filename.jpg`
* Multiple images: `image1 = file.jpg|Label`
* Header banner: `header_image = banner.jpg`
* Section: `== Section Name ==`
* Collapsed section: `=== Section Name ===`
* Subgroups: `::: Group Name :::`
* Custom CSS: class = `custom-style`
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
