We are tying to build a CMS for a small website. the process should kind of look like this:
we mark a page as something, we define some fields for it inside the page itself. In the page we can use those fields, 
and they will be displayed in the backend for that page so that admins can do data entry.

We need to be able to give pages a type id to mark them as belonging to a category

```injectablephp
DefinePage('home', 'Home');
DefinePage('archive', 'Archivio');
DefinePageTemplate('product', 'Prodotto');

DefinePage('case-histroy', 'Case History');
DefinePageTemplate('case-histroy-item', 'Case History Item');
```

We need an admin backend. This backend needs to have a sidebar to show the various options we have.
- Pages: it shows all registered pages entries, allowing to edit their data.
- One entry per template type: clicking on a template type shows all instances of that template (for example, all concrete products), and, clicking on them, allows us to edit their data.

We need a function to retrive the data for a template page, given its slug. So an archive (or case-history) page can iterate through all products (or items) and display them.

