# CONTRIBUTING

## New Issue

Before reporting an [issue](https://gitlab.com/francoisjacquet/scholapro/issues/), please consider the following recommendations:

1. Search [closed issues](https://gitlab.com/francoisjacquet/scholapro/issues?scope=all&utf8=%E2%9C%93&state=closed) or the [Wiki](https://gitlab.com/francoisjacquet/scholapro/wikis), your problem may already have been addressed. Please do not create **duplicates**.

2. Specify your ScholaPro, PHP & PostgreSQL **versions**, along with the server & browser used.

3. Provide **steps to reproduce** the problem.

4. Attach a **screenshot**.

5. ScholaPro errors, bugs (PHP, SQL, JS errors) & design or logic errors are welcome. _500 Internal Server Error_ messages can be found in the Apache `error.log` file.

6. **Installation problems**: ScholaPro has been succesfully installed on various environments; nevertheless, you may encounter errors [specific to your OS, PHP or PostgreSQL version or configuration](https://gitlab.com/francoisjacquet/scholapro/blob/mobile/INSTALL.md#scholapro-student-information-system). For the same reasons, installation problems will likely not be solved here.

7. **ScholaPro use**: the Handbooks, the inline Help & the [Wiki](https://gitlab.com/francoisjacquet/scholapro/wikis) contain useful resources to help you get the most out of ScholaPro. For all your questions about ScholaPro use and school administration, you can discuss them in the [forum](https://www.scholapro.org/forum/).

8. **Email support**: to get professional help with installation problems, or ScholaPro configuration, please head to https://www.scholapro.org/services/

You have PHP web development skills? Please head to the next section & send a [merge request](https://docs.gitlab.com/ee/user/project/merge_requests/creating_merge_requests.html).


## Contributing to ScholaPro

Please head to the offical [Contribute page](https://www.scholapro.org/contribute) to learn about how you can contribute to the project.

### Coding standards

1. We _roughly_ follow the [Wordpress Coding Standards](https://make.wordpress.org/core/handbook/coding-standards/).

2. [Comment your code](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/): we use PHPDoc.

3. Quality Assurance: we use code linters & other [QA tools](https://phpqa.io/)

4. Testing: Activate [debug mode](https://gitlab.com/francoisjacquet/scholapro/blob/mobile/INSTALL.md#optional-variables); for emails, we use [MailCatcher](http://mailcatcher.me/)

### Architecture

https://www.scholapro.org/wp-content/uploads/2016/06/scholapro-folders-files-architecture.png

### Meta

The [meta](https://gitlab.com/francoisjacquet/scholapro-meta/) repository provides tools to debug and scripts to run tests, QA and prepare ScholaPro release.

### Example Module

Freely study and reuse the [Example module](https://gitlab.com/francoisjacquet/Example)

