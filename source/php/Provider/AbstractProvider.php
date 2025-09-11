<?php

namespace AlgoliaIndex\Provider;

interface AbstractProvider
{
    public function setSettings(array $settings = []);

    public function search(string $query);

    public function clearObjects();

    public function deleteObject(string $objectId);

    public function deleteObjects(array $objectIds);

    public function saveObject(array $object, array $options = []);

    public function saveObjects(array $objects, array $options = []);

    public function getObjects(array $objectIds) : array;
}