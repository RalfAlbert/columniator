# Columniator #

Class for WordPress to convert content into columns

This class is in an early beta and will be develop by time.

If you want to split the post content into columns, there are several solutions to do this. The easiest way is to use CSS3. But this wont work in all browsers (IE, you know!?). So you hav eto do this by yourself. This clas could help you to do this job.

This is **not** a readymade plugin you cann download and install. The repository contain currently only one sample plugin. **You have to write your own plugin!**  

## What will the class do with the content? ##

The class will split the content in blocks with a configurable number of columns. Each block is divided by a configurable divider. Each column contains a configurable number of **words** and will protect some HTML tags so they won't be disrupted on two columns.

Each column will be applied with a CSS class. The columns themself can be wrapped in a container with another CSS class. So if you choose e.g. a `hr` tag as divider and wrap the columns in a `div` container with a CSS class called `.content-columns` and your columns are floated by CSS, you can apply a clearing to the `hr` tag by adding a `.content-columns hr { clear: both; }` to your stylesheet. 

## Writing a plugin ##

Start with a basic plugin. Create the plugin header and add a function which will convert your post contents. Add the function to a suitable hook. The sample plugin use the `the_post` hook because it use `<!--nextpage-->`as divider between the content blocks. If you use simpler dividers, such as `<hr />`, you can also use `the_content` as hook.

Include the class, create a new instance from it and configure the class.

## How to configure the class ##

Start with the number of columns, the number of words per column and the divider.

	$cap = new Columninator();

	// set words per column
	$cap->words_per_col = 30;

	// set number of columns
	$cap->num_cols      = 3;

	// set the divider between each columns-block
	$cap->cols_divider  = '<hr />';

Now define the CSS classes for each column. This willbe done by an array with a list of CSS classes. The first entry is for the first column, the second entry for the second column, and so on. If you define less CSS classes as number of columns, the higher numbered columns get the last CSS class you have defined.

Example: You have defined two CSS classes (`first-col, second-col`) and set the number of columns to four, the first column will have the CSS class `first-col`, the second column the CSS class `second-col` as expected. The tird and fourth column will also get the CSS class `second-col`, because it is the last defined CSS class.

On the other hand, if you define more CSS classes as number of columns, the last defined CSS classes will be ignored.

**This behavior will be changed in a future version!** 

	// set css classes for column #1, #2 & #3
	$cap->cols_css      = array( 'left-column', 'middle-column', 'right-column' );


The last step is optional. You can define a wrapper for the columns, so you can apply some more CSS to the columns-block. This could be neccassary if you define a `hr` tag as divider and arrange your columns with floats. To apply a clearing after the columns-block you can apply a clearing to all `hr` elemnts inside the wrapper class.

	// wrap the columns in this container
	$cap->wrapper_for_cols = '<div class="content-columns">%s</div>';

In the example above we choose a `hr` tag as divider and assume that the columns are arranged with `float: left`. In the stylesheet we can do a clearing after each columns-block with `.content-columns hr { clear: both; }`

## Some are protected, others are not ##

The class will protect some HTML tags by disrupting in two or more columns. Currently that are links, headings, images, `div` container, blockquotes and cites. All other HTML tags will be 'repaired' by the class.

For example, if the string `<strong>Hello wolrd!</strong>` will be split into two columns and the class will output something like

	<div class="left-column"><strong>Hello</strong></div>
	<div class="right-column"><strong>wolrd!</strong></div>

If the content contains HTML tags with a class or style, this could be lead to problems. The reason is that the class did not copy the class/style. A string like `<span class="foobar">Hello wolrd!</span>` will end in this result

	<div class="left-column"><span class="foobar">Hello</span></div>
	<div class="right-column"><span>wolrd!</span></div>

To avoid disrupting such elements, you can add them to the list of protected HTML.

	$cap->html_tags['spans'] = '#<span(.+)</span>#uUis';

This will add **all** `span` tags to the list of protected HTML tags. Each element in the list of protected HTML tags need a index and a regular expression to search for the HTML tags.

**This behavior will be changed in a future version!**

In a future version of the class, the class will keep the attributes of a disrupted HTML tag. 