<?php

/*
 * Comment Bundle
 * This file is part of the BardisCMS.
 *
 * (c) George Bardis <george@bardis.info>
 *
 */

namespace BardisCMS\CommentBundle\Repository;

use Doctrine\ORM\EntityRepository;

class CommentRepository extends EntityRepository {

    public function getCommentsForBlogPost($blogPostId, $approved = true) {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id, c.title, c.username, c.comment, c.created')
            ->where('c.blogPost = :blog_id')
            ->setParameter('blog_id', $blogPostId);

        if (false === is_null($approved)){
            $qb->andWhere('c.approved = :approved')->setParameter('approved', $approved);
        }

        $qb->orderBy('c.created', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getCommentById($commentId, $approved = true) {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id, c.title, c.username, c.comment, c.created')
            ->where('c.id = :comment_id')
            ->setParameter('comment_id', $commentId);

        if (false === is_null($approved)){
            $qb->andWhere('c.approved = :approved')->setParameter('approved', $approved);
        }

        $qb->orderBy('c.created', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
