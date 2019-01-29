<?php

namespace App\Generator\src\Commands;

use Illuminate\Console\Command;
use App\Generator\src\DbOracle;
use App\Generator\src\Table;
use App\Permission;

class Crud extends Command
{
    public $tableName;
    public $table;
    public $export;
    public $fields;
    public $fieldsArr;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fa:crud
        {tableName : The name of the table you want to generate crud for.}';

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
        $this->fields=DbOracle::fields($this->tableName);
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
        // }else
        //     $this->export=false;
        $this->generateModel();
        // $this->generateRouteModelBinding();
        $this->generateRoute();
        $this->generateAutocomplete();
        $this->generateController();
        $this->generateViews();
        $this->generatePermissions();
    }

    public function generatePermissions(){
      $mapPermission = collect(config('laratrust_seeder.permissions_map'));
      // dd($mapPermission);
      foreach ($mapPermission as $key => $permissionValue) {
        $permission = \App\Permission::firstOrCreate([
            'name' => $this->tableName . '-' . $permissionValue,
            'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($this->tableName),
            'description' => ucfirst($permissionValue) . ' ' . ucfirst($this->tableName),
            'module'=> $this->tableName
        ]);
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
        $routesFile = app_path('Http/routes.php');
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

    protected function getUpdatedAutocomplete($existingAutocomplete,$autocomplete){
      $str = "autocomplete goes here";

      if( strpos( $existingAutocomplete, $str ) !== false )
          return str_replace( $str, "{$str}\n\t".$autocomplete, $existingAutocomplete );

      return null;
    }

    public function generateController()
    {
        $controllerFile = $this->controllersDir().'/'.$this->controllerClassName().".php";

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
        $modelFile = $this->modelsDir().'/Models/'.$this->modelClassName().".php";

        if($this->confirmOverwrite($modelFile))
        {
            $content = view( $this->templatesDir().'.model', [
                'gen' => $this,
                'fields' => $this->fields,
                'fieldsArr' => $this->fieldsArr,
                'table'=>$this->table
            ]);
            file_put_contents($modelFile, $content);
            $this->info( "Model class ".$this->modelClassName()." generated successfully." );
        }
    }

    public function generateViews()
    {
        if( !file_exists($this->viewsDir()) ) mkdir($this->viewsDir());
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

    public function generateAutocomplete()
    {
        $controllerFile = $this->controllersDir()."/AutocompleteController.php";
        if(!file_exists($controllerFile)){
          $content = view($this->templatesDir().'.autocomplete');
          file_put_contents($controllerFile, $content);
          $this->info( "AutocompleteController generated successfully." );

          $route = "Route::get('autocomplete/{method}','AutocompleteController@search');\n";
          $routesFile = app_path('Http/routes.php');
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
  					$fieldName = preg_replace("/id_|_id/","",$field->name);
            $autocompleteFunctionName='private function '.$fieldName.'($r)';
            $autocompleteFileContent = file_get_contents($controllerFile);
            if ( strpos( $autocompleteFileContent, $autocompleteFunctionName ) == false )
            {
              $autocomplete = '/*'.$autocompleteFunctionName.'{
          $q=$r->input("q");
          $query='.ucfirst($fieldName).'::select(DB::raw("'.$fieldName.'.*"))
          ->where("name","like","%$q%")
          ->limit(20);
          $results=$query->get();
          return \Response::json($results->toArray());
        }
        */      ';

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
        return str_slug(str_replace("_"," ", ($this->tableName)));
    }

    public function controllerClassName()
    {
        // $this->error(("fendi_tes"));
        return studly_case(($this->tableName))."Controller";
    }

    public function viewsDir()
    {
        return base_path('resources/views/'.$this->viewsDirName());
    }

    public function viewsDirName()
    {
        return ($this->tableName);
    }

    public function controllersDir()
    {
        return app_path('Http/Controllers');
    }

    public function modelsDir()
    {
        return app_path();
    }

    public function modelClassName()
    {
        return studly_case(($this->tableName));
    }

    public function modelVariableName()
    {
        return camel_case(($this->tableName));
    }

    public function titleSingular()
    {
        return ucwords((str_replace("_", " ", $this->tableName)));
    }

    public function titlePlural()
    {
        return ucwords(str_replace("_", " ", $this->tableName));
    }

    public function templatesDir()
    {
        return config('crud.core_templates');
    }

}
