<?php

namespace App\Generator\src\Commands;

use Illuminate\Console\Command;
use App\Generator\src\DbOracle;
use App\Generator\src\Table;
use App\Module;
use App\Permission;

class ModuleCrud extends Command
{
    public $tableName;
    public $moduleName;
    public $table;
    public $module;
    public $export;
    public $fields;
    public $fieldsArr;
    public $prefixName=null;
    public $pureTableName;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fa:module-crud
        {moduleName : on this module}
        {tableName : generate crud for this table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate crud for a specific table in the database';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->tableName = $this->argument('tableName');
        $this->pureTableName = studly_case($this->tableName);
        // preg_match("/tref_|tm_|tr_|tconf_|td_/i", $this->tableName, $matches);
        // if(count($matches>0)){
        //   $this->prefixName = str_replace("_","", (ucfirst(strtolower($matches[0]))));
        //   $tmp = str_replace(strtolower($matches[0]),"",strtolower($this->tableName));
        //   $this->pureTableName = studly_case($tmp);
        // }
        // echo($this->prefixName);
        // echo($this->pureTableName);
        // die();
        $this->moduleName = ucfirst(strtolower($this->argument('moduleName')));
        $this->module = \Module::findOrFail($this->moduleName);
        $this->fields=DbOracle::fields($this->tableName);
        // dd($this->module->getPath());
        $this->table=new Table();
        $this->table->fields=$this->fields;
        $this->table->name=$this->tableName;
        // dd($this->table);

        $this->fieldsArr=[];
        foreach ($this->fields as $key => $value) {
            $this->fieldsArr[]=$value->name;
        }
        // dd($fieldsArr);
        $this->authAttr = str_replace('_', '-', $this->tableName);

        // if($this->confirm('Generate export? [y|N]')){
        //     $this->export=true;
        // }else{
        //   $this->export=false;
        // }
        $this->generateModel();
        // die();
        // $this->generateRouteModelBinding();
        $this->generateRoute();
        $this->generateController();
        $this->generateAutocomplete();
        $this->generateViews();
        $this->generatePermissions();
    }

    public function generatePermissions(){
      $mapPermission = collect(config('laratrust_seeder.permissions_map'));
      // dd($mapPermission);
      foreach ($mapPermission as $key => $permissionValue) {
        $permission = \App\Permission::firstOrNew([
            'name' => $this->authAttr . '-' . $permissionValue
        ]);
        $permission->display_name = ucfirst($permissionValue) . ' ' . $this->pureTableName;
        $permission->description = ucfirst($permissionValue) . ' ' . $this->pureTableName;
        $permission->module= $this->tableName;
        $permission->save();
        $this->info('Creating Permission to '.$permissionValue);
      }
    }

    public function generateRouteModelBinding()
    {
        $declaration = "\$router->model('".$this->route()."', 'App\Models\\".$this->modelClassName()."');";
        $providerFile = app_path('Providers/RouteServiceProvider.php');
        $fileContent = file_get_contents($providerFile);

        if ( strpos( $fileContent, $declaration ) == false )
        {
            $regex = "/(public\s*function\s*boot\s*\(\s*Router\s*.router\s*\)\s*\{)/";
            if( preg_match( $regex, $fileContent ) )
            {
                $fileContent = preg_replace( $regex, "$1\n\t\t".$declaration, $fileContent );
                file_put_contents($providerFile, $fileContent);
                $this->info("Route model binding inserted successfully in ".$providerFile);
                return true;
            }

            // match was not found for some reason
            $this->warn("Could not add route model binding for the route '".$this->route()."'.");
            $this->warn("Please add the following line manually in {$providerFile}:");
            $this->warn($declaration);
            return false;
        }

        // already exists
        $this->info("Model binding for the route: '".$this->route()."' already exists.");
        $this->info("Skipping...");
        return false;
    }

    public function generateRoute()
    {
        $route  = "Route::get('{$this->route()}/load-data','{$this->controllerClassName()}@loadData');\n";
        if($this->export){
            $route  .= "Route::post('{$this->route()}/export-data','{$this->controllerClassName()}@postExportData');\n";
        }
        $route .= "Route::resource('{$this->route()}','{$this->controllerClassName()}');\n";
        $route .= "Route::delete('{$this->route()}/{id}/restore','{$this->controllerClassName()}@restore');\n";
        $routesFile = $this->getPathFromModule('Http/routes.php');
        $routesFileContent = file_get_contents($routesFile);

        if ( strpos( $routesFileContent, $route ) == false )
        {
            $routesFileContent = $this->getUpdatedContent($routesFileContent, $route);
            file_put_contents($routesFile,$routesFileContent);
            $this->info("created route: ".$route);

            return true;
        }

        $this->info("Route: '".$route."' already exists.");
        $this->info("Skipping...");
        return false;
    }

    protected function getUpdatedContent ( $existingContent, $route )
    {
        // check if the user has directed to add routes
        $str = "nvd-crud routes go here";
        if( strpos( $existingContent, $str ) !== false )
            return str_replace( $str, "{$str}\n\t".$route, $existingContent );

        // check for 'web' middleware group
        $regex = "/(Route\s*\:\:\s*group\s*\(\s*\[\s*\'middleware\'\s*\=\>\s*\[\s*\'web\'\s*\]\s*\]\s*\,\s*function\s*\(\s*\)\s*\{)/";
        if( preg_match( $regex, $existingContent ) )
            return preg_replace( $regex, "$1\n\t".$route, $existingContent );

        // if there is no 'web' middleware group
        return $existingContent."\n".$route;
    }

    public function generateController()
    {
        $controllerFile = $this->controllersDir().'/'.$this->controllerClassName().".php";
        if(!file_exists($this->controllersDir())){
          mkdir($this->controllersDir(),0775,true);
        }
        if($this->confirmOverwrite($controllerFile))
        {
            // dd($this->export);
            $content = view($this->templatesDir().'.controller',['gen' => $this,
                'fields' => $this->fields,
                'fieldsArr' => $this->fieldsArr,
                'table'=>$this->table,
                'export'=>$this->export,
                ]);
            file_put_contents($controllerFile, $content);
            $this->info( $this->controllerClassName()." generated successfully." );
        }
    }

    public function generateModel()
    {
        if(!file_exists($this->getPathFromModule('Models'))){
          mkdir($this->getPathFromModule('Models'));
        }
        $modelFile = $this->getPathFromModule('Models/'.$this->modelClassName().".php");

        if($this->confirmOverwrite($modelFile))
        {
            $content = view( $this->templatesDir().'.model', [
                'gen' => $this,
                'fields' => $this->fields,
                'fieldsArr' => $this->fieldsArr,
                'table'=>$this->table
            ]);
            // die($content->render());
            file_put_contents($modelFile, $content);
            $this->info( "Model class ".$this->modelClassName()." generated successfully." );
        }
    }

    public function generateViews()
    {
        if( !file_exists($this->viewsDir()) ) mkdir($this->viewsDir(),0775,true);
        foreach ( config('crud.views') as $view ){
            $viewFile = $this->viewsDir()."/".$view.".blade.php";
            if($this->confirmOverwrite($viewFile))
            {
                $content = view( $this->templatesDir().'.views.'.$view, [
                    'gen' => $this,
                    'fields' => $this->fields,
                    'fieldsArr' => $this->fieldsArr,
                    'export'=>$this->export,
                    'table'=>$this->table
                ]);
                // dd($content);
                file_put_contents($viewFile, $content);
                // echo $viewFile;exit;

                $this->info( "View file ".$view." generated successfully." );
            }
        }
    }

    protected function confirmOverwrite($file)
    {
        // if file does not already exist, return
        if( !file_exists($file) ) return true;

        // file exists, get confirmation
        if ($this->confirm($file.' already exists! Do you wish to overwrite this file? [y|N]')) {
            $this->info("overwriting...");
            return true;
        }
        else{
            $this->info("Using existing file ...");
            return false;
        }
    }

    public function route()
    {
        return str_slug(str_replace("_"," ", ($this->pureTableName)));
    }

    public function controllerClassName()
    {
        // $this->error(("fendi_tes"));
        return studly_case(($this->pureTableName))."Controller";
    }

    public function viewsDir()
    {
        return $this->getPathFromModule('Resources/views/'.$this->viewsDirName());
    }

    public function viewsDirName()
    {
        return ($this->pureTableName);
    }

    public function controllersDir()
    {
        return $this->getPathFromModule('Http/Controllers');
    }

    public function modelsDir()
    {
        return $this->module->getPath();
    }

    public function modelClassName()
    {
        return $this->pureTableName;
    }

    public function modelVariableName()
    {
        return camel_case(($this->pureTableName));
    }

    public function titleSingular()
    {
        return ucwords((str_replace("_", " ", $this->pureTableName)));
    }

    public function titlePlural()
    {
        return ucwords(str_replace("_", " ", $this->pureTableName));
    }

    public function templatesDir()
    {
        return config('crud.modules_templates');
    }

    public function getPathFromModule($path){
      $pathArr = explode('/',$path);
      $temp = implode('/',$pathArr);
      return $this->module->getPath()."/".$temp;
    }

    public function getPrefix($prefix){
      preg_match("/tref_|tm_|tr_|tconf_|td_/i", $prefix, $matches);
      $retPrefix = null;

      if(count($matches)>0){
        $retPrefix = str_replace("_","", (ucfirst(strtolower($matches[0]))));
      }
      return $retPrefix;
    }

    public function removePrefix($data){
      $data = preg_replace("/tref_|tm_|tr_|tconf_|td_/i","",$data);
      return $data;
    }

    protected function getUpdatedAutocomplete($existingAutocomplete,$autocomplete){
      $str = "autocomplete goes here";

      if( strpos( $existingAutocomplete, $str ) !== false )
          return str_replace( $str, "{$str}\n\t".$autocomplete, $existingAutocomplete );

      return null;
    }

    public function generateAutocomplete()
    {
        $controllerFile = $this->controllersDir()."/AutocompleteController.php";
        if(!file_exists($controllerFile)){
          $content = view($this->templatesDir().'.autocomplete',[
              'gen' => $this,
              // 'fields' => $this->fields,
              // 'fieldsArr' => $this->fieldsArr,
              // 'export'=>$this->export,
              // 'table'=>$this->table
          ]);

          file_put_contents($controllerFile, $content);
          $this->info( "AutocompleteController generated successfully." );

          $route = "Route::get('autocomplete/{method}','AutocompleteController@search');\n";
          $routesFile = $this->getPathFromModule('Http/routes.php');
          $routesFileContent = file_get_contents($routesFile);

          if ( strpos( $routesFileContent, $route ) == false )
          {
              $routesFileContent = $this->getUpdatedContent($routesFileContent, $route);
              file_put_contents($routesFile,$routesFileContent);
              $this->info("created route: ".$route);
          }
        }

        foreach($this->fields as $field){
          if(($field->type == 'number')&&(preg_match("/id_|_id/",$field->name,$match))){
            $fieldName = strtolower(preg_replace("/id_|_id/","",$field->name));

            $autocompleteFunctionName='private function '.$fieldName.'($r)';
            $autocompleteFileContent = file_get_contents($controllerFile);
            if ( strpos( $autocompleteFileContent, $autocompleteFunctionName ) == false )
            {
              $autocomplete = ''.$autocompleteFunctionName.'{
          $q=strtoupper($r->input("q"));
          $query=Models\\'.studly_case($fieldName).'::select(\DB::raw("*"))
          ->where("upper('.$fieldName.')","like","%$q%")
          ->limit(20);
          $results=$query->get();
          return \Response::json($results->toArray());
        }
        ';

              $autocompleteFileContent = $this->getUpdatedAutocomplete($autocompleteFileContent, $autocomplete);
              if($autocompleteFileContent!=null){
                file_put_contents($controllerFile,$autocompleteFileContent);
                $this->info("append autocomplete");
              }
            }else{
              $this->info("autocomplete function exists");
            }
          }
        }
    }
}
