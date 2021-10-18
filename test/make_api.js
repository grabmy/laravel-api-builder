const expect = require("chai").expect;
const { execSync } = require("child_process");
const fs = require('fs');

let failCount = 0;

afterEach(function() {
    if (this.currentTest.state == 'failed') {
        failCount++;
    }
});

describe("Run make:api", function() {

    const migrationFile = '../../../database/migrations/0000____create_test_test_table.php';
    const modelFile = '../../../app/TestTest.php';
    const controllerFile = '../../../app/http/Controllers/TestTestController.php';

    // Clean files
    output = execSync("rm -fr " + migrationFile + " " + modelFile + " " + controllerFile, { timeout: 8000 }).toString();

    it("Artisan list must work and have make:api in list", function() {
        console.log('      php artisan list');

        let error = null;
        let output = "";
        try {
            output = execSync("cd ../../../ && php artisan list", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("make:api");
        expect(output).not.contain("No files generated due to errors");
    });

    it("Run with a file that does not exist returns error", function() {
        console.log('      php artisan make:api ./vendor/grabmy/laravel-api-builder/does_not_exist.json');

        let error = null;
        let output = "";
        try {
            output = execSync("cd ../../../ && php artisan make:api ./vendor/grabmy/laravel-api-builder/does_not_exist.json", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("File \"./vendor/grabmy/laravel-api-builder/does_not_exist.json\" does not exist");
        expect(output).contain("No files generated due to errors");
    });

    it("Run with an empty JSON file returns error", function() {
        console.log('      php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/empty.json');

        let error = null;
        let output = "";
        try {
            output = execSync("cd ../../../ && php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/empty.json", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("Could not parse JSON in file \"./vendor/grabmy/laravel-api-builder/test/json/empty.json\"");
        expect(output).contain("No files generated due to errors");
    });

    it("Run with wrong version returns error", function() {
        console.log('      php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/wrong_version.json');

        let error = null;
        let output = "";
        try {
            output = execSync("cd ../../../ && php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/wrong_version.json", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("Wrong version number");
        expect(output).contain("No files generated due to errors");
    });

    it("Run with no model returns warning", function() {
        console.log('      php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/no_model.json');

        let error = null;
        let output = "";
        try {
            output = execSync("cd ../../../ && php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/no_model.json", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).not.contain("Error");
        expect(output).not.contain("No files generated due to errors");
    });
    
    it("Make a migration and model with all field types", function() {
        console.log('      php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/model_types.json');

        let error = null;
        let output = "";
        try {
            output = execSync("cd ../../../ && php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/model_types.json", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).not.contain("Error");
        expect(output).not.contain("errors");
        expect(output).not.contain("No files generated due to errors");

        // Validate migration file
        error = null;
        expect(fs.existsSync(migrationFile)).ok;
        try {
            output = execSync("php -l " + migrationFile, { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("No syntax errors detected");
        
        // Controller must not exists
        expect(fs.existsSync(controllerFile)).not.ok;

        // Validate model file
        error = null;
        expect(fs.existsSync(modelFile)).ok;
        try {
            output = execSync("php -l " + modelFile, { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("No syntax errors detected");
        
        // Execute migrate:fresh
        error = null;
        try {
            output = execSync("cd ../../../ && php artisan migrate:fresh", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).not.contain("error");
        expect(output).not.contain("Error");
        
        // Cleaning Database
        output = execSync("cd ../../../ && php artisan migrate:rollback", { timeout: 8000 }).toString();

        // Cleaning files
        output = execSync("rm -fr " + migrationFile + " " + modelFile + " " + controllerFile, { timeout: 8000 }).toString();
    });
    
    it("Make a CRUD API with table article", function() {
        console.log('      php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/crud_article.json');

        let error = null;
        let output = "";
        try {
            output = execSync("cd ../../../ && php artisan make:api ./vendor/grabmy/laravel-api-builder/test/json/crud_article.json", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).not.contain("Error");
        expect(output).not.contain("errors");
        expect(output).not.contain("No files generated due to errors");

        // Validate migration file
        error = null;
        expect(fs.existsSync(migrationFile)).ok;
        try {
            output = execSync("php -l " + migrationFile, { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("No syntax errors detected");
        
        // Controller must not exists
        expect(fs.existsSync(controllerFile)).not.ok;

        // Validate model file
        error = null;
        expect(fs.existsSync(modelFile)).ok;
        try {
            output = execSync("php -l " + modelFile, { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).contain("No syntax errors detected");
        
        // Execute migrate:fresh
        error = null;
        try {
            output = execSync("cd ../../../ && php artisan migrate:fresh", { timeout: 8000 }).toString();
        } catch (e) {
            error = e;
        }
        expect(error).to.be.null;
        expect(output).not.contain("error");
        expect(output).not.contain("Error");
        
        // Test API
        
        // Cleaning Database
        output = execSync("cd ../../../ && php artisan migrate:rollback", { timeout: 8000 }).toString();

        // Cleaning files
        output = execSync("rm -fr " + migrationFile + " " + modelFile + " " + controllerFile, { timeout: 8000 }).toString();
    });
    
});