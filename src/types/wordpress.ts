import { Entity } from '@wordpress/core-data';

// Definir una interfaz que extienda Entity
export interface WPStock extends Entity {
  id: number;
  title: { rendered: string };
  content: { rendered: string };
  slug: string;
  date: string;
  status: 'publish' | 'draft' | 'pending' | 'private';
  author: number;
  featured_media: number;
  meta: Record<string, any>;
  // Agrega otros campos específicos según tus necesidades
}