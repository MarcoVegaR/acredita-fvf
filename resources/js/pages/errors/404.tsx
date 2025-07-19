import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertTriangle, Home, ArrowLeft } from 'lucide-react';

export default function NotFound() {
  return (
    <>
      <Head title="Página no encontrada" />
      
      <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div className="sm:mx-auto sm:w-full sm:max-w-md">
          <div className="text-center">
            <AlertTriangle className="mx-auto h-12 w-12 text-yellow-500" />
            <h1 className="mt-4 text-4xl font-bold text-gray-900">404</h1>
            <h2 className="mt-2 text-lg font-medium text-gray-700">
              Página no encontrada
            </h2>
          </div>
          
          <Card className="mt-8">
            <CardHeader>
              <CardTitle className="text-center">¡Oops!</CardTitle>
            </CardHeader>
            <CardContent className="text-center space-y-4">
              <p className="text-gray-600">
                La página que estás buscando no existe o ha sido movida.
              </p>
              
              <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <Button 
                  onClick={() => window.history.back()}
                  variant="outline"
                  className="flex items-center gap-2"
                >
                  <ArrowLeft className="h-4 w-4" />
                  Volver atrás
                </Button>
                
                <Link href="/dashboard">
                  <Button className="flex items-center gap-2 w-full sm:w-auto">
                    <Home className="h-4 w-4" />
                    Ir al Dashboard
                  </Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </>
  );
}
