import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import { paginationUtils } from "@/utils/pagination.utils";

type Props = {
  activePage: number;
  total: number;
  limit: number;
};

export default function PagePagination({ activePage, total, limit }: Props) {
  return (
    total > 0 && (
      <Pagination>
        <PaginationContent>
          <PaginationItem>
            <PaginationPrevious
              disabled={activePage === 1}
              className="bg-transparent"
              page={activePage - 1}
            />
          </PaginationItem>

          {paginationUtils
            .getPaginationItems(activePage, Math.ceil(total / limit))
            .map((item, index) => (
              <PaginationItem key={index}>
                {typeof item === "string" ? (
                  <PaginationEllipsis />
                ) : (
                  <PaginationLink
                    isActive={item === activePage}
                    page={item}
                  >
                    {item}
                  </PaginationLink>
                )}
              </PaginationItem>
            ))}

          <PaginationItem>
            <PaginationNext
              className="bg-transparent"
              disabled={activePage === Math.ceil(total / limit)}
              page={activePage + 1}
            />
          </PaginationItem>
        </PaginationContent>
      </Pagination>
    )
  );
}
