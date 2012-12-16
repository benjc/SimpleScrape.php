simpleScrape.php
================

What is this?
-------------
SimpleScrape is an easy-to-use content scraper for php, driven by a simple scripting language to describe where and what you want scraped from the source.

SimpleScrape deals with all the fiddly scraping, leaving you free to concentrate on what to do with the results. What’s more, should the website you’re scraping change its format you only need modify your script- your code remains untouched.

How does it work?
-----------------
At the simplest level, SimpleScrape requires just two things: a source (where you want to grab content from- typically a url, but it could just as easily be a file or string) and a script (a description of what you want to grab from the source).

Your script describes what you want to grab by saying what you know will be near it. So, for example if you wanted to grab the title of a webpage, you know two things:

    1. There will be a <title> tag directly before it.
    2. There will be a </title> tag directly after it.

In terms of explaining this in our script we would write this as:

    <title>[GRAB:Title]</title>

This is all SimpleScrape needs to grab whatever it finds between the first occurrence of <title> and </title> and store it in an array with a key of Title.

How do I grab more than one thing?
----------------------------------
You can enter as many lines in your script file as you like. Each new line will be treated as a separate search and will be searched for in the source following on from where the previous line’s search reached.

So, if we wanted to grab both the title and first h1 tag in a page we would write it like this:

    <title>[GRAB:Title]</title>
    <h1>[GRAB:H1]</h1>

Note that by placing the second grab on a whole new line we’re saying that any amount of content could occur between the title and h1 tags.

It should also be noted that if any line in the script does not return a match then the script will stop at that point.

What if I want to search for the same thing again and again?
------------------------------------------------------------
Let’s assume the source contains more than one h1 tag and we want to grab them all. Simply enclose the grab line with REPEAT like so:

    [REPEAT]
    <h1>[GRAB:H1]</h1>
    [/REPEAT]

This will keep grabbing h1 tags until no more matches are found.

You can also limit the number of repetitions by specifying a number in the opening REPEAT. The following script will perform 3 grabs (assuming 3 matches are found):

    [REPEAT:3]
    <h1>[GRAB:H1]</h1>
    [/REPEAT]

Enough explaining, just give me an example
------------------------------------------
Okay, here’s how we’d do a straightforward scrape of the title tag from www.google.com…

    <?php
    require("simpleScrape.php");
 
    $scraper = new simpleScrape();
 
    $scraper->sourceURL = "http://www.google.com";  // Specify source to scrape
    $scraper->scriptPath = "myscript.txt";  // Specify script file
 
    $values = $scraper->scrape();  // Perform scrape and return results as array
 
    echo "Title text is: ".$values["Title"][0];
    ?>

Where myscript.txt is:

    <title>[GRAB:Title]</title>

Of course, you can do a lot more than just grab the title of a page. If you’re here you probably already have something in mind. Just play around with it, see what you can do and let me know if you have any questions, comments or bug reports.
