
# DokuWiki Infobox Plugin

A flexible infobox plugin for DokuWiki that creates structured information boxes for any type of content.

![Example Image](https://github.com/user-attachments/assets/bf87a183-5810-4cd2-80fb-2c56d8eae090)

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

Syntax Reference
----------------
* Single image: image = filename.jpg
* Multiple images: image1 = file.jpg|Label
* Header banner: header_image = banner.jpg
* Section: == Section Name ==
* Collapsed section: === Section Name ===
* Custom CSS: class = custom-style
* Lists in values: Use wiki list syntax
