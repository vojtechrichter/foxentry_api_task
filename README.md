# Building the containers

Both PHP and Elastic containers can be ran by executing the following command:
```
docker-compose up --build
```
If you get an error about not existing network, add the `--force-recreate' flag:
```
docker-compose up --build --force-recreate
```

# Note about the `es_data` directory

The `es_data` directory serves as a persistent storage of the elastic data, which is written while the container is running.
Some data are included to provide examples of products, however, if you wish to remove it, and start with empty indexes, run the following commands:
```
rm -rf es_data/
```
Now, run the containers, and the `es_data` directory will be created from the specified volume in `docker-compose.yml`. If you get an elastic error about obtaining node locks, executing the following command should fix it:
```
sudo chown -R 1000:1000 es_data/
```
