# Using nginx

The bundled `.htaccess` files are what keep the application source, the database schema, and
the uploaded attachments from being served directly. nginx ignores them, so add the
equivalent rules to your server block:

```nginx
# Never serve application source or the schema.
location ~ ^/(core|models|controllers|views|database|vendors|lang|modules)/ {
    deny all;
}

# Attachments are served by the app, never directly, and never executed.
location ^~ /attachments/ {
    deny all;
}

location = /config.php {
    deny all;
}

autoindex off;
```
