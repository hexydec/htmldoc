# HTMLdoc: Performance

HTMLdoc has been written with performance in mind, as its main purpose is for the minification of HTML. Since PHP is widely used to generate HTML, it is expected and designed for the use case that minification will happen on the fly.

For most HTML documents the speed and memory usage will be well within acceptable boundaries, although if you put large complex documents into it with a large amount of nodes, the speed will suffer.

For the most part the object is memory efficient, most memory will be used by the tokenisation process, which uses a regular expression to split the input string into tokens. This part of the program has been optimised to minimise the amount of memory used.

Memory and speed can be tested using the [index.php](../index.php) file bundled in the repository.
